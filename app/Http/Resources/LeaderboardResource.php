<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaderboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'rank' => $this->rank,
            'points' => $this->points,
            'wins' => $this->wins,
            'losses' => $this->losses,
            'draws' => $this->draws,
            'games_played' => $this->games_played,
            'win_percentage' => $this->win_percentage,
            'period_type' => $this->period_type,
            'period_key' => $this->period_key,
            'user' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'username' => $this->user?->username,
                'avatar_url' => $this->user?->avatar_url,
                'is_online' => $this->user?->is_online,
            ],
            'game' => $this->whenLoaded('game', fn () => [
                'id' => $this->game?->id,
                'name' => $this->game?->name,
                'slug' => $this->game?->slug,
            ]),
        ];
    }
}
