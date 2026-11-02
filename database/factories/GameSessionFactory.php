<?php

namespace Database\Factories;

use App\Enums\GameSessionStatus;
use App\Models\Game;
use App\Models\GameSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<GameSession>
 */
class GameSessionFactory extends Factory
{
    protected $model = GameSession::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'game_id' => Game::factory(),
            'room_id' => null,
            'created_by' => User::factory(),
            'status' => GameSessionStatus::Active,
            'difficulty' => 'medium',
            'state' => [
                'players' => [],
                'board' => array_fill(0, 9, null),
            ],
            'current_turn_user_id' => null,
            'winner_id' => null,
            'is_draw' => false,
            'started_at' => now(),
        ];
    }
}
