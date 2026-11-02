<?php

namespace Database\Factories;

use App\Models\Achievement;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Achievement>
 */
class AchievementFactory extends Factory
{
    protected $model = Achievement::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'slug' => Str::slug($name),
            'name' => Str::title($name),
            'description' => fake()->sentence(8),
            'icon' => 'trophy',
            'tier' => fake()->randomElement(['bronze', 'silver', 'gold']),
            'criteria_type' => 'wins',
            'criteria_value' => fake()->numberBetween(1, 50),
            'points_reward' => fake()->numberBetween(10, 500),
            'is_active' => true,
        ];
    }
}
