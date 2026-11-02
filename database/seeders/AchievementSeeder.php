<?php

namespace Database\Seeders;

use App\Models\Achievement;
use Illuminate\Database\Seeder;

class AchievementSeeder extends Seeder
{
    public function run(): void
    {
        $achievements = [
            ['slug' => 'first-win', 'name' => 'First Blood', 'description' => 'Win your very first match.', 'icon' => 'trophy', 'tier' => 'bronze', 'criteria_type' => 'wins', 'criteria_value' => 1, 'points_reward' => 25],
            ['slug' => 'five-wins', 'name' => 'Getting Warm', 'description' => 'Win 5 matches.', 'icon' => 'flame', 'tier' => 'bronze', 'criteria_type' => 'wins', 'criteria_value' => 5, 'points_reward' => 50],
            ['slug' => 'twenty-five-wins', 'name' => 'Seasoned Champion', 'description' => 'Win 25 matches.', 'icon' => 'medal', 'tier' => 'silver', 'criteria_type' => 'wins', 'criteria_value' => 25, 'points_reward' => 150],
            ['slug' => 'century-club', 'name' => 'Century Club', 'description' => 'Win 100 matches.', 'icon' => 'crown', 'tier' => 'gold', 'criteria_type' => 'wins', 'criteria_value' => 100, 'points_reward' => 500],
            ['slug' => 'rookie', 'name' => 'Rookie', 'description' => 'Play your first game.', 'icon' => 'gamepad-2', 'tier' => 'bronze', 'criteria_type' => 'games_played', 'criteria_value' => 1, 'points_reward' => 10],
            ['slug' => 'regular', 'name' => 'Regular', 'description' => 'Play 10 games.', 'icon' => 'calendar-check', 'tier' => 'bronze', 'criteria_type' => 'games_played', 'criteria_value' => 10, 'points_reward' => 40],
            ['slug' => 'marathon', 'name' => 'Marathoner', 'description' => 'Play 50 games.', 'icon' => 'timer', 'tier' => 'silver', 'criteria_type' => 'games_played', 'criteria_value' => 50, 'points_reward' => 120],
            ['slug' => 'hot-streak', 'name' => 'Hot Streak', 'description' => 'Win 3 matches in a row.', 'icon' => 'flame', 'tier' => 'bronze', 'criteria_type' => 'streak', 'criteria_value' => 3, 'points_reward' => 40],
            ['slug' => 'unstoppable', 'name' => 'Unstoppable', 'description' => 'Win 7 matches in a row.', 'icon' => 'zap', 'tier' => 'gold', 'criteria_type' => 'streak', 'criteria_value' => 7, 'points_reward' => 200],
            ['slug' => 'point-collector', 'name' => 'Point Collector', 'description' => 'Reach 1,000 points.', 'icon' => 'coins', 'tier' => 'silver', 'criteria_type' => 'points', 'criteria_value' => 1000, 'points_reward' => 100],
            ['slug' => 'high-roller', 'name' => 'High Roller', 'description' => 'Reach 5,000 points.', 'icon' => 'gem', 'tier' => 'gold', 'criteria_type' => 'points', 'criteria_value' => 5000, 'points_reward' => 300],
            ['slug' => 'team-player', 'name' => 'Team Player', 'description' => 'Win 5 multiplayer matches.', 'icon' => 'users', 'tier' => 'silver', 'criteria_type' => 'multiplayer_wins', 'criteria_value' => 5, 'points_reward' => 100],
            ['slug' => 'tic-tac-toe-ace', 'name' => 'Grid Master', 'description' => 'Win 10 Tic-Tac-Toe matches.', 'icon' => 'grid-3x3', 'tier' => 'silver', 'criteria_type' => 'tictactoe_wins', 'criteria_value' => 10, 'points_reward' => 100],
            ['slug' => 'rps-champion', 'name' => 'Hand Champion', 'description' => 'Win 10 Rock Paper Scissors matches.', 'icon' => 'swords', 'tier' => 'silver', 'criteria_type' => 'rps_wins', 'criteria_value' => 10, 'points_reward' => 100],
            ['slug' => 'number-hunter', 'name' => 'Number Hunter', 'description' => 'Win 10 Number Guess matches.', 'icon' => 'hash', 'tier' => 'silver', 'criteria_type' => 'number_guess_wins', 'criteria_value' => 10, 'points_reward' => 100],
        ];

        foreach ($achievements as $achievement) {
            Achievement::query()->updateOrCreate(['slug' => $achievement['slug']], $achievement);
        }
    }
}
