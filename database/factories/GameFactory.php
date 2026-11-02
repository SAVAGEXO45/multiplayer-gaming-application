<?php

namespace Database\Factories;

use App\Models\Game;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Game>
 */
class GameFactory extends Factory
{
    protected $model = Game::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1000, 9999),
            'name' => Str::title($name),
            'tagline' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'instructions' => fake()->paragraph(),
            'rules' => fake()->paragraph(),
            'icon' => 'gamepad-2',
            'accent' => 'cyan',
            'min_players' => 1,
            'max_players' => 2,
            'supports_multiplayer' => true,
            'is_active' => true,
            'points_win' => 100,
            'points_draw' => 40,
            'points_loss' => -10,
            'points_participation' => 5,
            'difficulties' => ['easy', 'medium', 'hard'],
            'sort_order' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
