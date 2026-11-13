<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GameRoomResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'room_code' => $this->room_code,
            'name' => $this->name,
            'visibility' => $this->visibility,
            'status' => $this->status,
            'max_players' => $this->max_players,
            'host_user_id' => $this->host_user_id,
            'game' => new GameResource($this->whenLoaded('game')),
            'host' => new UserResource($this->whenLoaded('host')),
            'players' => $this->whenLoaded('roomPlayers', fn () => $this->roomPlayers->map(fn ($rp) => [
                'user_id' => $rp->user_id,
                'name' => $rp->user?->name,
                'username' => $rp->user?->username,
                'avatar_url' => $rp->user?->avatar_url,
                'slot' => $rp->player_slot,
                'symbol' => $rp->player_symbol,
                'status' => $rp->status,
                'is_ready' => $rp->is_ready,
                'score' => $rp->score,
            ])->values()),
            'active_session' => new GameSessionResource($this->whenLoaded('activeSession')),
            'started_at' => $this->started_at?->toIso8601String(),
            'ended_at' => $this->ended_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
