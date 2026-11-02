<?php

namespace Database\Factories;

use App\Enums\RoomStatus;
use App\Models\Game;
use App\Models\GameRoom;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<GameRoom>
 */
class GameRoomFactory extends Factory
{
    protected $model = GameRoom::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => Str::upper(Str::random(6)),
            'name' => fake()->words(3, true),
            'game_id' => Game::factory(),
            'host_id' => User::factory(),
            'max_players' => 2,
            'status' => RoomStatus::Waiting,
            'is_private' => false,
            'password' => null,
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn (): array => [
            'status' => RoomStatus::InProgress,
            'started_at' => now(),
        ]);
    }
}
