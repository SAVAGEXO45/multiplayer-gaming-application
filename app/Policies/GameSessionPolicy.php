<?php

namespace App\Policies;

use App\Models\GameSession;
use App\Models\User;

class GameSessionPolicy
{
    public function view(User $user, GameSession $session): bool
    {
        return $user->isAdmin() || $session->involves($user);
    }

    public function move(User $user, GameSession $session): bool
    {
        return $session->isActive() && $session->involves($user);
    }

    public function resign(User $user, GameSession $session): bool
    {
        return $session->isActive() && $session->involves($user);
    }
}
