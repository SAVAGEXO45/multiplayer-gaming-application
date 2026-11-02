<?php

namespace App\Policies;

use App\Models\GameRoom;
use App\Models\User;

class GameRoomPolicy
{
    public function view(User $user, GameRoom $room): bool
    {
        return true;
    }

    public function start(User $user, GameRoom $room): bool
    {
        return $room->host_id === $user->id;
    }

    public function leave(User $user, GameRoom $room): bool
    {
        return $room->hasPlayer($user);
    }

    public function update(User $user, GameRoom $room): bool
    {
        return $user->isAdmin() || $room->host_id === $user->id;
    }
}
