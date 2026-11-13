<?php

namespace App\Services;

use App\Jobs\RecalculateLeaderboardsJob;
use App\Models\Game;
use App\Models\GameSession;
use App\Models\Leaderboard;
use App\Models\PlayerStatistic;
use App\Models\RoomPlayer;
use App\Models\User;
use App\Models\UserGameActivity;
use Illuminate\Support\Facades\DB;

class ScoringService
{
    public function __construct(
        private readonly AchievementService $achievementService,
        private readonly NotificationService $notificationService
    ) {}

    /**
     * Record a solo or arcade action game run, update personal high score, library activity,
     * player statistics, leaderboards, and unlock achievements.
     */
    public function recordSoloRunScore(User $user, Game $game, int $score, int $level = 1, bool $isVictory = true): array
    {
        $score = max(0, min(2000000, $score));
        $level = max(1, min(99, $level));

        $baseWinPoints = $game->points_per_win ?? 120;
        $bonusPoints = min(500, (int) floor($score / 25));
        $pointsEarned = max(15, ($isVictory ? $baseWinPoints : (int) floor($baseWinPoints * 0.4)) + $bonusPoints);

        $weekKey = now()->format('o-\WW');
        $monthKey = now()->format('Y-m');

        return DB::transaction(function () use ($user, $game, $score, $level, $isVictory, $pointsEarned, $weekKey, $monthKey) {
            $activity = UserGameActivity::firstOrCreate(
                ['user_id' => $user->id, 'game_id' => $game->id],
                [
                    'is_favorite' => false,
                    'plays_count' => 0,
                    'high_score' => 0,
                    'max_level' => 1,
                ]
            );

            $isNewHighScore = $score > $activity->high_score;
            $activity->update([
                'plays_count' => $activity->plays_count + 1,
                'high_score' => max($activity->high_score, $score),
                'max_level' => max($activity->max_level, $level),
                'last_played_at' => now(),
            ]);

            $game->increment('plays_count');

            // Update global and game-specific player statistics
            $this->updateStatisticRow($user->id, null, $isVictory, false, $pointsEarned);
            $this->updateStatisticRow($user->id, $game->id, $isVictory, false, $pointsEarned);

            // Update leaderboards
            $periods = [
                ['type' => 'global', 'key' => 'all'],
                ['type' => 'weekly', 'key' => $weekKey],
                ['type' => 'monthly', 'key' => $monthKey],
            ];

            foreach ($periods as $period) {
                $this->updateLeaderboardRow($user->id, null, $period['type'], $period['key'], $isVictory, false, $pointsEarned);
                $this->updateLeaderboardRow($user->id, $game->id, $period['type'], $period['key'], $isVictory, false, $pointsEarned);
            }

            $unlocked = $this->achievementService->checkAndUnlock($user);
            $this->recalculateRankings($game->id, $weekKey, $monthKey);

            return [
                'score' => $score,
                'high_score' => $activity->high_score,
                'max_level' => $activity->max_level,
                'plays_count' => $activity->plays_count,
                'is_new_high_score' => $isNewHighScore,
                'points_earned' => $pointsEarned,
                'unlocked_achievements' => collect($unlocked)->map(fn ($a) => [
                    'name' => $a->name,
                    'code' => $a->code,
                    'points_reward' => $a->points_reward,
                ])->values()->all(),
            ];
        });
    }

    /**
     * Record match outcome, award points, update player statistics and leaderboards.
     */
    public function processCompletedSession(GameSession $session): void
    {
        if ($session->status !== 'completed') {
            return;
        }

        $session->loadMissing(['game', 'room.roomPlayers.user']);
        $game = $session->game;
        $roomPlayers = $session->room->roomPlayers->whereIn('status', ['active', 'ready', 'disconnected']);

        if ($roomPlayers->count() < 2) {
            return;
        }

        $winPoints = $game->points_per_win ?? 100;
        $drawPoints = $game->points_per_draw ?? 25;
        $participationPoints = 10;

        $weekKey = now()->format('o-\WW');
        $monthKey = now()->format('Y-m');

        DB::transaction(function () use ($session, $game, $roomPlayers, $winPoints, $drawPoints, $participationPoints, $weekKey, $monthKey) {
            foreach ($roomPlayers as $roomPlayer) {
                $user = $roomPlayer->user;
                if (!$user) {
                    continue;
                }

                $isWinner = !$session->is_draw && (int) $session->winner_user_id === (int) $user->id;
                $isDraw = (bool) $session->is_draw;

                $pointsEarned = $isWinner
                    ? $winPoints
                    : ($isDraw ? $drawPoints : $participationPoints);

                if ($isWinner) {
                    $roomPlayer->increment('score', 1);
                }

                // 1. Update Global PlayerStatistic (game_id = null)
                $this->updateStatisticRow($user->id, null, $isWinner, $isDraw, $pointsEarned);

                // 2. Update Game-Specific PlayerStatistic (game_id = $game->id)
                $this->updateStatisticRow($user->id, $game->id, $isWinner, $isDraw, $pointsEarned);

                // 3. Update Leaderboards (Global, Weekly, Monthly — both overall & game-specific)
                $periods = [
                    ['type' => 'global', 'key' => 'all'],
                    ['type' => 'weekly', 'key' => $weekKey],
                    ['type' => 'monthly', 'key' => $monthKey],
                ];

                foreach ($periods as $period) {
                    $this->updateLeaderboardRow($user->id, null, $period['type'], $period['key'], $isWinner, $isDraw, $pointsEarned);
                    $this->updateLeaderboardRow($user->id, $game->id, $period['type'], $period['key'], $isWinner, $isDraw, $pointsEarned);
                }

                // 4. Evaluate Achievements
                $this->achievementService->checkAndUnlock($user);

                // 5. Send Match Result Notification
                $outcomeTitle = $isWinner
                    ? "Victory in {$game->name}!"
                    : ($isDraw ? "Draw in {$game->name}" : "Match Completed in {$game->name}");
                $outcomeMessage = $isWinner
                    ? "You won the match and earned +{$pointsEarned} points!"
                    : ($isDraw
                        ? "The match ended in a draw. You earned +{$pointsEarned} points."
                        : "Tough battle! You earned +{$pointsEarned} participation points.");

                $this->notificationService->notify(
                    $user,
                    'match_result',
                    $outcomeTitle,
                    $outcomeMessage,
                    route('rooms.show', $session->room->room_code),
                    [
                        'session_id' => $session->id,
                        'game_slug' => $game->slug,
                        'points_earned' => $pointsEarned,
                        'outcome' => $isWinner ? 'win' : ($isDraw ? 'draw' : 'loss'),
                    ]
                );
            }

            $this->recalculateRankings($game->id, $weekKey, $monthKey);
        });

        RecalculateLeaderboardsJob::dispatch($game->id);
    }

    public function recalculateRankings(?int $gameId = null, ?string $weekKey = null, ?string $monthKey = null): void
    {
        $weekKey ??= now()->format('o-\WW');
        $monthKey ??= now()->format('Y-m');

        // Update rank_position in PlayerStatistic (global and game-specific)
        $statGameIds = array_unique([null, $gameId]);
        foreach ($statGameIds as $gId) {
            $stats = PlayerStatistic::query()
                ->when($gId === null, fn ($q) => $q->whereNull('game_id'), fn ($q) => $q->where('game_id', $gId))
                ->orderByDesc('total_points')
                ->orderByDesc('games_won')
                ->orderByDesc('win_percentage')
                ->get();

            $pos = 1;
            foreach ($stats as $stat) {
                if ($stat->rank_position !== $pos) {
                    $stat->update(['rank_position' => $pos]);
                }
                $pos++;
            }
        }

        // Update rank in Leaderboards
        $periods = [
            ['type' => 'global', 'key' => 'all'],
            ['type' => 'weekly', 'key' => $weekKey],
            ['type' => 'monthly', 'key' => $monthKey],
        ];

        foreach ($statGameIds as $gId) {
            foreach ($periods as $period) {
                $entries = Leaderboard::query()
                    ->where('period_type', $period['type'])
                    ->where('period_key', $period['key'])
                    ->when($gId === null, fn ($q) => $q->whereNull('game_id'), fn ($q) => $q->where('game_id', $gId))
                    ->orderByDesc('points')
                    ->orderByDesc('wins')
                    ->orderByDesc('win_percentage')
                    ->get();

                $rank = 1;
                foreach ($entries as $entry) {
                    if ($entry->rank !== $rank) {
                        $entry->update(['rank' => $rank]);
                    }
                    $rank++;
                }
            }
        }
    }

    private function updateStatisticRow(int $userId, ?int $gameId, bool $isWinner, bool $isDraw, int $pointsEarned): void
    {
        $stat = PlayerStatistic::firstOrCreate(
            ['user_id' => $userId, 'game_id' => $gameId],
            [
                'games_played' => 0,
                'games_won' => 0,
                'games_lost' => 0,
                'games_drawn' => 0,
                'win_percentage' => 0,
                'total_points' => 0,
                'current_streak' => 0,
                'best_streak' => 0,
            ]
        );

        $gamesPlayed = $stat->games_played + 1;
        $gamesWon = $stat->games_won + ($isWinner ? 1 : 0);
        $gamesDrawn = $stat->games_drawn + ($isDraw ? 1 : 0);
        $gamesLost = $stat->games_lost + (!$isWinner && !$isDraw ? 1 : 0);
        $winPercentage = $gamesPlayed > 0 ? round(($gamesWon / $gamesPlayed) * 100, 2) : 0.00;
        $currentStreak = $isWinner ? ($stat->current_streak + 1) : 0;
        $bestStreak = max($stat->best_streak, $currentStreak);

        $stat->update([
            'games_played' => $gamesPlayed,
            'games_won' => $gamesWon,
            'games_lost' => $gamesLost,
            'games_drawn' => $gamesDrawn,
            'win_percentage' => $winPercentage,
            'total_points' => $stat->total_points + $pointsEarned,
            'current_streak' => $currentStreak,
            'best_streak' => $bestStreak,
        ]);
    }

    private function updateLeaderboardRow(
        int $userId,
        ?int $gameId,
        string $periodType,
        string $periodKey,
        bool $isWinner,
        bool $isDraw,
        int $pointsEarned
    ): void {
        $entry = Leaderboard::firstOrCreate(
            [
                'user_id' => $userId,
                'game_id' => $gameId,
                'period_type' => $periodType,
                'period_key' => $periodKey,
            ],
            [
                'points' => 0,
                'wins' => 0,
                'losses' => 0,
                'draws' => 0,
                'games_played' => 0,
                'win_percentage' => 0,
            ]
        );

        $gamesPlayed = $entry->games_played + 1;
        $wins = $entry->wins + ($isWinner ? 1 : 0);
        $draws = $entry->draws + ($isDraw ? 1 : 0);
        $losses = $entry->losses + (!$isWinner && !$isDraw ? 1 : 0);
        $winPercentage = $gamesPlayed > 0 ? round(($wins / $gamesPlayed) * 100, 2) : 0.00;

        $entry->update([
            'points' => $entry->points + $pointsEarned,
            'wins' => $wins,
            'losses' => $losses,
            'draws' => $draws,
            'games_played' => $gamesPlayed,
            'win_percentage' => $winPercentage,
        ]);
    }
}
