<?php

namespace App\Services;

use App\Enums\GameResultType;
use App\Models\GameResult;
use App\Models\PlayerStatistics;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PlayerStatisticsService
{
    public function record(User $user, GameResult $result, bool $multiplayer): PlayerStatistics
    {
        return DB::transaction(function () use ($user, $result, $multiplayer): PlayerStatistics {
            /** @var PlayerStatistics $statistics */
            $statistics = PlayerStatistics::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrCreate(['user_id' => $user->id]);

            $statistics->games_played++;
            $statistics->total_score += $result->score;
            $statistics->last_game_at = now();

            if ($result->result === GameResultType::Win) {
                $statistics->wins++;
                $statistics->current_streak++;
                $statistics->best_streak = max($statistics->best_streak, $statistics->current_streak);

                if ($multiplayer) {
                    $statistics->multiplayer_wins++;
                }
            } elseif ($result->result === GameResultType::Draw) {
                $statistics->draws++;
                $statistics->current_streak = 0;
            } else {
                $statistics->losses++;
                $statistics->current_streak = 0;
            }

            $statistics->save();

            return $statistics;
        });
    }

    public function recompute(User $user): PlayerStatistics
    {
        $results = GameResult::query()->where('user_id', $user->id)->get();

        $statistics = PlayerStatistics::query()->firstOrCreate(['user_id' => $user->id]);
        $statistics->fill([
            'games_played' => $results->count(),
            'wins' => $results->where('result', GameResultType::Win)->count(),
            'losses' => $results->where('result', GameResultType::Loss)->count(),
            'draws' => $results->where('result', GameResultType::Draw)->count(),
            'multiplayer_wins' => $results->where('result', GameResultType::Win)->where('is_multiplayer', true)->count(),
            'total_score' => $results->sum('score'),
            'last_game_at' => $results->max('created_at'),
        ]);
        $statistics->save();

        return $statistics;
    }
}
