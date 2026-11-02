<?php

namespace Database\Factories;

use App\Models\Reward;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Reward>
 */
class RewardFactory extends Factory
{
    protected $model = Reward::class;

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
            'type' => fake()->randomElement(['title', 'badge', 'theme']),
            'icon' => 'gift',
            'cost' => fake()->numberBetween(50, 1000),
            'payload' => null,
            'stock' => null,
            'is_active' => true,
        ];
    }
}
