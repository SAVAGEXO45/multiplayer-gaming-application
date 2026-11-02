<?php

namespace Database\Factories;

use App\Enums\GameResultType;
use App\Models\Game;
use App\Models\GameResult;
use App\Models\GameSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameResult>
 */
class GameResultFactory extends Factory
{
    protected $model = GameResult::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_session_id' => GameSession::factory(),
            'user_id' => User::factory(),
            'game_id' => Game::factory(),
            'result' => fake()->randomElement(GameResultType::cases()),
            'score' => fake()->numberBetween(0, 100),
            'points_awarded' => fake()->numberBetween(0, 150),
            'is_multiplayer' => false,
            'metadata' => null,
        ];
    }
}
