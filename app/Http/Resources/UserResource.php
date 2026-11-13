<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->when($request->user()?->id === $this->id || $request->user()?->isAdmin(), $this->email),
            'role' => $this->role,
            'avatar_url' => $this->avatar_url,
            'bio' => $this->bio,
            'is_online' => $this->is_online,
            'is_suspended' => $this->is_suspended,
            'last_seen_at' => $this->last_seen_at?->toIso8601String(),
            'statistics' => $this->whenLoaded('globalStatistic', fn () => [
                'games_played' => $this->globalStatistic->games_played,
                'games_won' => $this->globalStatistic->games_won,
                'games_lost' => $this->globalStatistic->games_lost,
                'games_drawn' => $this->globalStatistic->games_drawn,
                'win_percentage' => $this->globalStatistic->win_percentage,
                'total_points' => $this->globalStatistic->total_points,
                'rank_position' => $this->globalStatistic->rank_position,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
