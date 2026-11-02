<?php

namespace App\Events;

use App\Models\GameSession;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameSessionUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly GameSession $session) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('game-session.'.$this->session->uuid)];
    }

    public function broadcastAs(): string
    {
        return 'session.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'uuid' => $this->session->uuid,
            'status' => $this->session->status->value,
            'current_turn_user_id' => $this->session->current_turn_user_id,
            'winner_id' => $this->session->winner_id,
            'is_draw' => $this->session->is_draw,
            'state' => $this->session->state,
        ];
    }
}
