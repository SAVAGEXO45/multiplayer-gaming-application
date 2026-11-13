<?php

namespace App\Events;

use App\Models\GameMove;
use App\Models\GameRoom;
use App\Models\GameSession;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameMoveMade implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public GameRoom $room,
        public GameSession $session,
        public GameMove $move
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('room.' . $this->room->room_code),
        ];
    }

    public function broadcastAs(): string
    {
        return 'game.move';
    }

    public function broadcastWith(): array
    {
        $boardState = $this->session->board_state;

        // Mask RPS choices while waiting for second player so clients cannot peek via WebSocket payload
        if (($boardState['type'] ?? '') === 'rock-paper-scissors' && !empty($boardState['current_choices'])) {
            $boardState['current_choices'] = array_fill_keys(array_keys($boardState['current_choices']), 'locked');
        }

        return [
            'room_code' => $this->room->room_code,
            'room_status' => $this->room->status,
            'session' => [
                'id' => $this->session->id,
                'session_number' => $this->session->session_number,
                'status' => $this->session->status,
                'current_turn_user_id' => $this->session->current_turn_user_id,
                'winner_user_id' => $this->session->winner_user_id,
                'is_draw' => $this->session->is_draw,
                'result_reason' => $this->session->result_reason,
                'board_state' => $boardState,
                'moves_count' => $this->session->moves_count,
            ],
            'move' => [
                'id' => $this->move->id,
                'user_id' => $this->move->user_id,
                'username' => $this->move->user?->username,
                'move_number' => $this->move->move_number,
                'move_data' => $this->move->move_data,
                'created_at' => $this->move->created_at?->toIso8601String(),
            ],
        ];
    }
}
