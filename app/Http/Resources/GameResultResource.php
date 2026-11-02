<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\GameResult */
class GameResultResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'game_session_id' => $this->game_session_id,
            'game_id' => $this->game_id,
            'result' => $this->result->value,
            'score' => $this->score,
            'points_awarded' => $this->points_awarded,
            'is_multiplayer' => $this->is_multiplayer,
            'metadata' => $this->metadata,
            'user' => new UserResource($this->whenLoaded('user')),
            'game' => new GameResource($this->whenLoaded('game')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
