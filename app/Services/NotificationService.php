<?php

namespace App\Services;

use App\Events\PlayerNotificationReceived;
use App\Models\PlatformNotification;
use App\Models\User;
use Throwable;

class NotificationService
{
    public function notify(
        User|int $user,
        string $type,
        string $title,
        string $message,
        ?string $actionUrl = null,
        array $data = []
    ): PlatformNotification {
        $userId = $user instanceof User ? $user->id : $user;

        $notification = PlatformNotification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'action_url' => $actionUrl,
            'data' => $data,
        ]);

        try {
            broadcast(new PlayerNotificationReceived($notification));
        } catch (Throwable) {
            // Do not fail core operation if Reverb WebSocket server is not actively listening
        }

        return $notification;
    }
}
