<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\PlayerStatistics */
class PlayerStatisticsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'games_played' => (int) $this->games_played,
            'wins' => (int) $this->wins,
            'losses' => (int) $this->losses,
            'draws' => (int) $this->draws,
            'points' => (int) $this->points,
            'current_streak' => (int) $this->current_streak,
            'best_streak' => (int) $this->best_streak,
            'multiplayer_wins' => (int) $this->multiplayer_wins,
            'win_rate' => $this->winRate(),
            'last_game_at' => $this->last_game_at?->toIso8601String(),
        ];
    }
}
