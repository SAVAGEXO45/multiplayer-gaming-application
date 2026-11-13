<?php

use App\Models\GameRoom;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function (User $user, int $id) {
    return (int) $user->id === $id;
});

Broadcast::channel('room.{roomCode}', function (User $user, string $roomCode) {
    $room = GameRoom::where('room_code', strtoupper($roomCode))->first();
    if (!$room) {
        return false;
    }

    if ($room->visibility === 'public' || $room->hasPlayer($user->id) || $user->isAdmin()) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'avatar_url' => $user->avatar_url,
        ];
    }

    return false;
});
