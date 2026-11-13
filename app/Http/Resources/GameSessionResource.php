<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GameSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $boardState = $this->board_state;

        // Mask opponent RPS choice until round resolves
        if (($boardState['type'] ?? '') === 'rock-paper-scissors' && !empty($boardState['current_choices'])) {
            $viewerId = $request->user()?->id;
            $masked = [];
            foreach ($boardState['current_choices'] as $uid => $choice) {
                $masked[$uid] = ((int) $uid === (int) $viewerId) ? $choice : 'locked';
            }
            $boardState['current_choices'] = $masked;
        }

        return [
            'id' => $this->id,
            'game_room_id' => $this->game_room_id,
            'game_id' => $this->game_id,
            'session_number' => $this->session_number,
            'status' => $this->status,
            'current_turn_user_id' => $this->current_turn_user_id,
            'winner_user_id' => $this->winner_user_id,
            'is_draw' => $this->is_draw,
            'board_state' => $boardState,
            'scores' => $this->scores,
            'result_reason' => $this->result_reason,
            'moves_count' => $this->moves_count,
            'moves' => $this->whenLoaded('moves', fn () => $this->moves->map(fn ($m) => [
                'id' => $m->id,
                'user_id' => $m->user_id,
                'username' => $m->user?->username,
                'move_number' => $m->move_number,
                'move_data' => $m->move_data,
                'created_at' => $m->created_at?->toIso8601String(),
            ])),
            'started_at' => $this->started_at?->toIso8601String(),
            'ended_at' => $this->ended_at?->toIso8601String(),
        ];
    }
}
