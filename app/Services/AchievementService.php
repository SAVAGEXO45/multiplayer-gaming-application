<?php

namespace App\Services;

use App\Enums\AchievementCriteria;
use App\Models\Achievement;
use App\Models\GameResult;
use App\Models\PlayerStatistics;
use App\Models\User;
use App\Models\UserAchievement;
use App\Notifications\AchievementUnlocked;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class AchievementService
{
    public function __construct(private readonly ScoringService $scoring) {}

    /**
     * @return Collection<int, Achievement>
     */
    public function evaluate(User $user): Collection
    {
        $unlockedIds = UserAchievement::query()
            ->where('user_id', $user->id)
            ->pluck('achievement_id')
            ->all();

        $candidates = Achievement::query()
            ->where('is_active', true)
            ->whereNotIn('id', $unlockedIds)
            ->get();

        $unlocked = collect();

        foreach ($candidates as $achievement) {
            if ($this->currentValue($user, $achievement) < $achievement->criteria_value) {
                continue;
            }

            $created = DB::transaction(function () use ($user, $achievement): bool {
                $record = UserAchievement::query()->firstOrCreate(
                    ['user_id' => $user->id, 'achievement_id' => $achievement->id],
                    ['unlocked_at' => now()],
                );

                if (! $record->wasRecentlyCreated) {
                    return false;
                }

                if ($achievement->points_reward > 0) {
                    $this->scoring->award(
                        $user,
                        $achievement->points_reward,
                        "Achievement unlocked: {$achievement->name}",
                        'achievement',
                        $achievement->id,
                    );
                }

                return true;
            });

            if ($created) {
                $unlocked->push($achievement);

                try {
                    $user->notify(new AchievementUnlocked($achievement));
                } catch (Throwable) {
                    // Notifications are best-effort and must never break gameplay.
                }
            }
        }

        return $unlocked;
    }

    private function currentValue(User $user, Achievement $achievement): int
    {
        $statistics = PlayerStatistics::query()->firstOrCreate(['user_id' => $user->id]);

        return match ($achievement->criteria_type) {
            AchievementCriteria::Wins->value => (int) $statistics->wins,
            AchievementCriteria::GamesPlayed->value => (int) $statistics->games_played,
            AchievementCriteria::Streak->value => (int) $statistics->best_streak,
            AchievementCriteria::Points->value => (int) $statistics->points,
            AchievementCriteria::MultiplayerWins->value => (int) $statistics->multiplayer_wins,
            AchievementCriteria::TicTacToeWins->value => $this->winsForGame($user, 'tic-tac-toe'),
            AchievementCriteria::RpsWins->value => $this->winsForGame($user, 'rock-paper-scissors'),
            AchievementCriteria::NumberGuessWins->value => $this->winsForGame($user, 'number-guess'),
            default => 0,
        };
    }

    private function winsForGame(User $user, string $slug): int
    {
        return GameResult::query()
            ->join('games', 'games.id', '=', 'game_results.game_id')
            ->where('game_results.user_id', $user->id)
            ->where('game_results.result', 'win')
            ->where('games.slug', $slug)
            ->count();
    }
}
