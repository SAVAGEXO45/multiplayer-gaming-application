<?php

namespace Database\Factories;

use App\Models\GameRoom;
use App\Models\RoomPlayer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoomPlayer>
 */
class RoomPlayerFactory extends Factory
{
    protected $model = RoomPlayer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_room_id' => GameRoom::factory(),
            'user_id' => User::factory(),
            'is_ready' => false,
            'is_host' => false,
            'score' => 0,
            'joined_at' => now(),
        ];
    }

    public function ready(): static
    {
        return $this->state(fn (): array => ['is_ready' => true]);
    }
}
