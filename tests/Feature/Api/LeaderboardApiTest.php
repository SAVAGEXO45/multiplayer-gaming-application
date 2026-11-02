<?php

use App\Enums\GameResultType;
use App\Models\GameResult;
use App\Models\GameSession;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

function recordResult(User $user, string $slug, GameResultType $type, int $points, ?DateTimeInterface $when = null): GameResult
{
    $game = App\Models\Game::query()->where('slug', $slug)->firstOrFail();

    $session = GameSession::factory()->create([
        'game_id' => $game->id,
        'created_by' => $user->id,
        'status' => 'finished',
        'state' => ['players' => [$user->id]],
    ]);

    return GameResult::factory()->create([
        'game_session_id' => $session->id,
        'user_id' => $user->id,
        'game_id' => $game->id,
        'result' => $type,
        'score' => 10,
        'points_awarded' => $points,
        'created_at' => $when ?? now(),
    ]);
}

it('ranks players by points on the global leaderboard', function () {
    createGame('tic-tac-toe');

    $leader = User::factory()->create(['username' => 'leader']);
    $runnerUp = User::factory()->create(['username' => 'runner_up']);
    $third = User::factory()->create(['username' => 'third']);

    $leader->statistics->update(['points' => 900, 'wins' => 9, 'games_played' => 12]);
    $runnerUp->statistics->update(['points' => 600, 'wins' => 6, 'games_played' => 10]);
    $third->statistics->update(['points' => 600, 'wins' => 5, 'games_played' => 11]);

    Sanctum::actingAs($leader);

    $response = $this->getJson('/api/leaderboard');

    $response->assertOk()
        ->assertJsonPath('data.0.username', 'leader')
        ->assertJsonPath('data.0.rank', 1)
        ->assertJsonPath('data.1.username', 'runner_up')
        ->assertJsonPath('data.2.username', 'third');
});

it('excludes suspended players from the leaderboard', function () {
    $active = User::factory()->create(['username' => 'active_player']);
    $banned = User::factory()->suspended()->create(['username' => 'banned_player']);

    $active->statistics->update(['points' => 100]);
    $banned->statistics->update(['points' => 900]);

    Sanctum::actingAs($active);

    $response = $this->getJson('/api/leaderboard');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.username', 'active_player');
});

it('only counts results from the current week on the weekly leaderboard', function () {
    createGame('tic-tac-toe');

    $user = User::factory()->create(['username' => 'weekly_player']);

    recordResult($user, 'tic-tac-toe', GameResultType::Win, 100, now());
    recordResult($user, 'tic-tac-toe', GameResultType::Win, 100, now()->subWeeks(3));

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/leaderboard/weekly');

    $response->assertOk()
        ->assertJsonPath('data.0.username', 'weekly_player')
        ->assertJsonPath('data.0.points', 100)
        ->assertJsonPath('data.0.games_played', 1)
        ->assertJsonPath('data.0.wins', 1);
});

it('filters the leaderboard by game', function () {
    createGame('tic-tac-toe');
    createGame('rock-paper-scissors');

    $user = User::factory()->create(['username' => 'multi_game']);
    $other = User::factory()->create(['username' => 'other_game']);

    recordResult($user, 'tic-tac-toe', GameResultType::Win, 100);
    recordResult($other, 'rock-paper-scissors', GameResultType::Win, 500);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/leaderboard?period=all&game=tic-tac-toe');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.username', 'multi_game')
        ->assertJsonPath('data.0.points', 100)
        ->assertJsonPath('meta.game', 'tic-tac-toe');
});

it('computes win rate and totals per game for the profile', function () {
    createGame('tic-tac-toe');

    $user = User::factory()->create();

    recordResult($user, 'tic-tac-toe', GameResultType::Win, 100);
    recordResult($user, 'tic-tac-toe', GameResultType::Win, 100);
    recordResult($user, 'tic-tac-toe', GameResultType::Loss, 5);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/profile');

    $response->assertOk()
        ->assertJsonPath('data.per_game.tic-tac-toe.games_played', 3)
        ->assertJsonPath('data.per_game.tic-tac-toe.wins', 2)
        ->assertJsonPath('data.per_game.tic-tac-toe.losses', 1)
        ->assertJsonPath('data.per_game.tic-tac-toe.points', 205)
        ->assertJsonPath('data.per_game.tic-tac-toe.win_rate', 66.7);
});
