<?php

namespace Database\Seeders;

use App\Models\Reward;
use Illuminate\Database\Seeder;

class RewardSeeder extends Seeder
{
    public function run(): void
    {
        $rewards = [
            ['slug' => 'rookie-title', 'name' => 'Rookie Title', 'description' => 'Display the "Rookie" title on your profile.', 'type' => 'title', 'icon' => 'sparkles', 'cost' => 100, 'payload' => 'Rookie', 'stock' => null],
            ['slug' => 'veteran-title', 'name' => 'Veteran Title', 'description' => 'Display the "Veteran" title on your profile.', 'type' => 'title', 'icon' => 'shield', 'cost' => 500, 'payload' => 'Veteran', 'stock' => null],
            ['slug' => 'legend-title', 'name' => 'Legend Title', 'description' => 'The most prestigious title in the arcade.', 'type' => 'title', 'icon' => 'crown', 'cost' => 2000, 'payload' => 'Legend', 'stock' => 100],
            ['slug' => 'neon-badge', 'name' => 'Neon Badge', 'description' => 'A glowing neon badge next to your name.', 'type' => 'badge', 'icon' => 'badge-check', 'cost' => 250, 'payload' => 'neon', 'stock' => null],
            ['slug' => 'flame-badge', 'name' => 'Flame Badge', 'description' => 'Show off your hot streak with a flame badge.', 'type' => 'badge', 'icon' => 'flame', 'cost' => 400, 'payload' => 'flame', 'stock' => null],
            ['slug' => 'midnight-theme', 'name' => 'Midnight Theme', 'description' => 'Unlock the exclusive midnight profile theme.', 'type' => 'theme', 'icon' => 'moon', 'cost' => 750, 'payload' => 'midnight', 'stock' => null],
            ['slug' => 'arcade-theme', 'name' => 'Arcade Theme', 'description' => 'A retro arcade-inspired profile theme.', 'type' => 'theme', 'icon' => 'gamepad-2', 'cost' => 1200, 'payload' => 'arcade', 'stock' => 50],
        ];

        foreach ($rewards as $reward) {
            Reward::query()->updateOrCreate(['slug' => $reward['slug']], [
                ...$reward,
                'is_active' => true,
            ]);
        }
    }
}
