<?php

namespace App\Notifications;

use App\Models\Achievement;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AchievementUnlocked extends Notification
{
    use Queueable;

    public function __construct(public readonly Achievement $achievement) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Achievement unlocked',
            'message' => "You unlocked \"{$this->achievement->name}\" and earned {$this->achievement->points_reward} points.",
            'icon' => $this->achievement->icon,
            'achievement_slug' => $this->achievement->slug,
        ];
    }
}
