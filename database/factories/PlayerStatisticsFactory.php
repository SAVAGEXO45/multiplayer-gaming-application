<?php

namespace Database\Factories;

use App\Models\PlayerStatistics;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlayerStatistics>
 */
class PlayerStatisticsFactory extends Factory
{
    protected $model = PlayerStatistics::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $wins = fake()->numberBetween(0, 50);
        $losses = fake()->numberBetween(0, 30);
        $draws = fake()->numberBetween(0, 10);

        return [
            'user_id' => User::factory(),
            'games_played' => $wins + $losses + $draws,
            'wins' => $wins,
            'losses' => $losses,
            'draws' => $draws,
            'points' => fake()->numberBetween(0, 5000),
            'current_streak' => 0,
            'best_streak' => fake()->numberBetween(0, 10),
            'multiplayer_wins' => 0,
            'total_score' => fake()->numberBetween(0, 1000),
            'last_game_at' => now(),
        ];
    }
}
