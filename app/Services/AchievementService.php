<?php

namespace App\Services;

use App\Models\Achievement;
use App\Models\Friendship;
use App\Models\Leaderboard;
use App\Models\PlayerAchievement;
use App\Models\PlayerStatistic;
use App\Models\User;

class AchievementService
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    /**
     * Evaluate all achievements for a user and unlock any newly satisfied criteria.
     *
     * @return array<int, Achievement>
     */
    public function checkAndUnlock(User $user): array
    {
        $unlockedIds = PlayerAchievement::where('user_id', $user->id)
            ->pluck('achievement_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $globalStat = PlayerStatistic::firstOrCreate(
            ['user_id' => $user->id, 'game_id' => null],
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

        $gameStats = PlayerStatistic::where('user_id', $user->id)
            ->whereNotNull('game_id')
            ->get()
            ->keyBy('game_id');

        $friendsCount = Friendship::where('user_id', $user->id)->count();

        $candidates = Achievement::all();
        $newlyUnlocked = [];

        foreach ($candidates as $achievement) {
            if (in_array((int) $achievement->id, $unlockedIds, true)) {
                continue;
            }

            $qualified = match ($achievement->criteria_type) {
                'games_played' => $globalStat->games_played >= $achievement->criteria_value,
                'games_won' => $globalStat->games_won >= $achievement->criteria_value,
                'total_points' => $globalStat->total_points >= $achievement->criteria_value,
                'win_streak' => $globalStat->best_streak >= $achievement->criteria_value,
                'friends_count' => $friendsCount >= $achievement->criteria_value,
                'game_specific_wins' => $achievement->game_id !== null
                    && ($gameStats->get($achievement->game_id)?->games_won ?? 0) >= $achievement->criteria_value,
                default => false,
            };

            if ($qualified) {
                PlayerAchievement::firstOrCreate([
                    'user_id' => $user->id,
                    'achievement_id' => $achievement->id,
                ], [
                    'unlocked_at' => now(),
                ]);

                if ($achievement->points_reward > 0) {
                    $globalStat->increment('total_points', $achievement->points_reward);
                    $this->addBonusPointsToGlobalLeaderboard($user->id, $achievement->points_reward);
                }

                $this->notificationService->notify(
                    $user,
                    'achievement_unlocked',
                    'Achievement Unlocked: ' . $achievement->name,
                    "You earned the \"{$achievement->name}\" badge (+{$achievement->points_reward} pts)!",
                    route('profile.show', $user->username),
                    ['achievement_id' => $achievement->id, 'reward' => $achievement->points_reward]
                );

                $newlyUnlocked[] = $achievement;
                $unlockedIds[] = (int) $achievement->id;
            }
        }

        return $newlyUnlocked;
    }

    private function addBonusPointsToGlobalLeaderboard(int $userId, int $bonusPoints): void
    {
        $entry = Leaderboard::firstOrCreate(
            [
                'user_id' => $userId,
                'game_id' => null,
                'period_type' => 'global',
                'period_key' => 'all',
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

        $entry->increment('points', $bonusPoints);
    }
}
