<?php

namespace App\Policies;

use App\Models\GameRoom;
use App\Models\User;

class GameRoomPolicy
{
    public function view(User $user, GameRoom $room): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($room->visibility === 'public') {
            return true;
        }

        return $room->hasPlayer($user->id);
    }

    public function join(User $user, GameRoom $room): bool
    {
        if ($user->is_suspended) {
            return false;
        }

        if ($room->hasPlayer($user->id)) {
            return true;
        }

        return in_array($room->status, ['waiting', 'in_progress'], true) && !$room->isFull();
    }

    public function makeMove(User $user, GameRoom $room): bool
    {
        return !$user->is_suspended && $room->hasPlayer($user->id);
    }

    public function manage(User $user, GameRoom $room): bool
    {
        return $user->isAdmin() || $room->isHost($user->id);
    }
}
