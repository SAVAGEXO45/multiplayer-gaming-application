<?php

namespace Database\Seeders;

use App\Models\Game;
use Illuminate\Database\Seeder;

class GameSeeder extends Seeder
{
    public function run(): void
    {
        $games = [
            [
                'slug' => 'tic-tac-toe',
                'name' => 'Tic-Tac-Toe',
                'tagline' => 'Classic three-in-a-row strategy',
                'description' => 'Take turns marking squares on a 3×3 grid. Line up three of your marks horizontally, vertically or diagonally before your opponent does.',
                'instructions' => "1. You play as X and move first.\n2. Tap any empty square to place your mark.\n3. The AI (or your opponent) answers with O.\n4. Get three in a row to win.",
                'rules' => "• Three matching marks in a row wins.\n• A full board with no winner is a draw.\n• You cannot play on an occupied square.\n• In multiplayer, players alternate turns.\n• Leaving a multiplayer match counts as a forfeit.",
                'icon' => 'grid-3x3',
                'accent' => 'cyan',
                'min_players' => 1,
                'max_players' => 2,
                'supports_multiplayer' => true,
                'points_win' => 100,
                'points_draw' => 40,
                'points_loss' => -10,
                'points_participation' => 5,
                'difficulties' => ['easy', 'medium', 'hard'],
                'sort_order' => 1,
            ],
            [
                'slug' => 'rock-paper-scissors',
                'name' => 'Rock Paper Scissors',
                'tagline' => 'Best of five, wits vs. chance',
                'description' => 'Pick rock, paper or scissors each round. Rock beats scissors, scissors beats paper, paper beats rock. First to three round wins takes the match.',
                'instructions' => "1. Choose rock, paper or scissors.\n2. Your opponent (or the AI) chooses at the same time.\n3. The round winner scores a point.\n4. First to 3 points wins the match.",
                'rules' => "• Rock beats scissors.\n• Scissors beats paper.\n• Paper beats rock.\n• Identical choices draw the round.\n• First to 3 round wins takes the match.\n• A tied match after 5 rounds is a draw.",
                'icon' => 'swords',
                'accent' => 'violet',
                'min_players' => 1,
                'max_players' => 2,
                'supports_multiplayer' => true,
                'points_win' => 120,
                'points_draw' => 50,
                'points_loss' => -10,
                'points_participation' => 5,
                'difficulties' => ['easy', 'medium', 'hard'],
                'sort_order' => 2,
            ],
            [
                'slug' => 'number-guess',
                'name' => 'Number Guess',
                'tagline' => 'Crack the secret number',
                'description' => 'A secret number is hidden in a range. Guess it before you run out of attempts. Higher difficulties shrink your attempts and widen the range.',
                'instructions' => "1. A secret number is generated for your difficulty.\n2. Enter a guess inside the shown range.\n3. You are told whether the secret is higher or lower.\n4. Find the number before you run out of attempts.",
                'rules' => "• Easy: 1–50 with 10 attempts.\n• Medium: 1–100 with 7 attempts.\n• Hard: 1–100 with 5 attempts.\n• Running out of attempts ends the game.\n• This game is solo only.",
                'icon' => 'hash',
                'accent' => 'amber',
                'min_players' => 1,
                'max_players' => 1,
                'supports_multiplayer' => false,
                'points_win' => 150,
                'points_draw' => 0,
                'points_loss' => 0,
                'points_participation' => 10,
                'difficulties' => ['easy', 'medium', 'hard'],
                'sort_order' => 3,
            ],
        ];

        foreach ($games as $game) {
            Game::query()->updateOrCreate(['slug' => $game['slug']], $game);
        }
    }
}
