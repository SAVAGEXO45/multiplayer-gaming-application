<?php

use App\Models\Game;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Create a seeded-equivalent game for the given engine slug.
 *
 * @param  array<string, mixed>  $attributes
 */
function createGame(string $slug, array $attributes = []): Game
{
    $presets = [
        'tic-tac-toe' => [
            'name' => 'Tic-Tac-Toe',
            'icon' => 'grid-3x3',
            'accent' => 'cyan',
            'min_players' => 1,
            'max_players' => 2,
            'supports_multiplayer' => true,
            'points_win' => 100,
            'points_draw' => 40,
            'points_loss' => -10,
            'points_participation' => 5,
            'sort_order' => 1,
        ],
        'rock-paper-scissors' => [
            'name' => 'Rock Paper Scissors',
            'icon' => 'swords',
            'accent' => 'violet',
            'min_players' => 1,
            'max_players' => 2,
            'supports_multiplayer' => true,
            'points_win' => 120,
            'points_draw' => 50,
            'points_loss' => -10,
            'points_participation' => 5,
            'sort_order' => 2,
        ],
        'number-guess' => [
            'name' => 'Number Guess',
            'icon' => 'hash',
            'accent' => 'amber',
            'min_players' => 1,
            'max_players' => 1,
            'supports_multiplayer' => false,
            'points_win' => 150,
            'points_draw' => 0,
            'points_loss' => 0,
            'points_participation' => 10,
            'sort_order' => 3,
        ],
    ];

    return Game::factory()->create(array_merge(
        ['slug' => $slug, 'is_active' => true],
        $presets[$slug] ?? [],
        $attributes,
    ));
}
