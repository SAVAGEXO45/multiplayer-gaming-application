<?php

namespace App\Events;

use App\Models\GameRoom;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoomUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public GameRoom $room,
        public string $action = 'updated',
        public array $meta = []
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('room.' . $this->room->room_code),
            new Channel('lobby'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'room.updated';
    }

    public function broadcastWith(): array
    {
        $this->room->loadMissing(['game', 'host', 'roomPlayers.user', 'activeSession']);

        return [
            'action' => $this->action,
            'room_code' => $this->room->room_code,
            'status' => $this->room->status,
            'host_user_id' => $this->room->host_user_id,
            'players' => $this->room->roomPlayers->map(fn ($rp) => [
                'user_id' => $rp->user_id,
                'name' => $rp->user?->name,
                'username' => $rp->user?->username,
                'avatar_url' => $rp->user?->avatar_url,
                'slot' => $rp->player_slot,
                'symbol' => $rp->player_symbol,
                'status' => $rp->status,
                'score' => $rp->score,
            ])->values()->all(),
            'session' => $this->room->activeSession ? [
                'id' => $this->room->activeSession->id,
                'session_number' => $this->room->activeSession->session_number,
                'status' => $this->room->activeSession->status,
                'current_turn_user_id' => $this->room->activeSession->current_turn_user_id,
                'winner_user_id' => $this->room->activeSession->winner_user_id,
                'is_draw' => $this->room->activeSession->is_draw,
                'result_reason' => $this->room->activeSession->result_reason,
                'board_state' => $this->room->activeSession->board_state,
                'moves_count' => $this->room->activeSession->moves_count,
            ] : null,
            'meta' => $this->meta,
        ];
    }
}
