<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\GameRoom */
class GameRoomResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'status' => $this->status->value,
            'max_players' => $this->max_players,
            'is_private' => $this->is_private,
            'has_password' => $this->hasPassword(),
            'host' => new UserResource($this->whenLoaded('host')),
            'game' => new GameResource($this->whenLoaded('game')),
            'players' => RoomPlayerResource::collection($this->whenLoaded('activePlayers')),
            'started_at' => $this->started_at?->toIso8601String(),
            'finished_at' => $this->finished_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
