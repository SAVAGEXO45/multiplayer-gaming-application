<?php

use App\Enums\GameResultType;
use App\Models\GameResult;
use App\Models\GameSession;
use App\Models\PlayerStatistics;
use App\Models\RewardTransaction;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

function ticTacToeSession(User $player, array $stateOverrides = [], array $attributes = []): GameSession
{
    $game = createGame('tic-tac-toe');

    return GameSession::factory()->create(array_merge([
        'game_id' => $game->id,
        'created_by' => $player->id,
        'status' => 'active',
        'difficulty' => 'easy',
        'state' => array_merge([
            'players' => [$player->id],
            'marks' => [(string) $player->id => 'X'],
            'board' => array_fill(0, 9, null),
            'move_count' => 0,
            'winning_line' => null,
            'last_move' => null,
        ], $stateOverrides),
        'current_turn_user_id' => $player->id,
        'winner_id' => null,
        'is_draw' => false,
    ], $attributes));
}

it('starts a solo game session', function () {
    $game = createGame('tic-tac-toe');
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/games/tic-tac-toe/start', ['difficulty' => 'medium']);

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 'active')
        ->assertJsonPath('data.game.slug', 'tic-tac-toe')
        ->assertJsonPath('data.state.players.0', $user->id);

    expect(GameSession::query()->where('game_id', $game->id)->count())->toBe(1);
});

it('rejects an invalid difficulty when starting a game', function () {
    createGame('tic-tac-toe');
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/games/tic-tac-toe/start', ['difficulty' => 'impossible'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('difficulty');
});

it('rejects starting a disabled game', function () {
    createGame('tic-tac-toe', ['is_active' => false]);
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/games/tic-tac-toe/start', ['difficulty' => 'easy'])
        ->assertUnprocessable();
});

it('rejects client supplied scores on a move', function () {
    $user = User::factory()->create();
    $session = ticTacToeSession($user);
    Sanctum::actingAs($user);

    $this->postJson("/api/games/{$session->uuid}/move", [
        'position' => 0,
        'points' => 99999,
        'score' => 99999,
        'winner_id' => $user->id,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['points', 'score', 'winner_id']);

    expect(GameResult::query()->count())->toBe(0);
});

it('rejects moves from players who are not in the session', function () {
    $player = User::factory()->create();
    $intruder = User::factory()->create();
    $session = ticTacToeSession($player);
    Sanctum::actingAs($intruder);

    $this->postJson("/api/games/{$session->uuid}/move", ['position' => 0])
        ->assertForbidden();
});

it('rejects moves played out of turn in multiplayer', function () {
    $first = User::factory()->create();
    $second = User::factory()->create();
    $session = ticTacToeSession($first, [
        'players' => [$first->id, $second->id],
        'marks' => [(string) $first->id => 'X', (string) $second->id => 'O'],
    ]);
    Sanctum::actingAs($second);

    $this->postJson("/api/games/{$session->uuid}/move", ['position' => 4])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('game');
});

it('rejects moves on an occupied square', function () {
    $user = User::factory()->create();
    $session = ticTacToeSession($user, [
        'board' => ['X', null, null, null, null, null, null, null, null],
        'move_count' => 1,
    ]);
    Sanctum::actingAs($user);

    $this->postJson("/api/games/{$session->uuid}/move", ['position' => 0])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('position');
});

it('awards server side points when a solo game is won', function () {
    $user = User::factory()->create();
    $session = ticTacToeSession($user, [
        'board' => ['X', 'X', null, 'O', 'O', null, null, null, null],
        'move_count' => 4,
    ]);
    Sanctum::actingAs($user);

    $response = $this->postJson("/api/games/{$session->uuid}/move", ['position' => 2]);

    $response->assertOk()
        ->assertJsonPath('data.status', 'finished')
        ->assertJsonPath('data.winner_id', $user->id);

    $result = GameResult::query()->where('game_session_id', $session->id)->sole();

    expect($result->result)->toBe(GameResultType::Win)
        ->and($result->points_awarded)->toBe(75) // 100 win points * easy multiplier
        ->and($result->is_multiplayer)->toBeFalse();

    $statistics = PlayerStatistics::query()->where('user_id', $user->id)->sole();

    expect($statistics->wins)->toBe(1)
        ->and($statistics->games_played)->toBe(1)
        ->and($statistics->points)->toBe(75)
        ->and($statistics->best_streak)->toBe(1);

    expect(RewardTransaction::query()->where('user_id', $user->id)->count())->toBe(1);
});

it('does not duplicate results or rewards when a finished game is submitted again', function () {
    $user = User::factory()->create();
    $session = ticTacToeSession($user, [
        'board' => ['X', 'X', null, 'O', 'O', null, null, null, null],
        'move_count' => 4,
    ]);
    Sanctum::actingAs($user);

    $this->postJson("/api/games/{$session->uuid}/move", ['position' => 2])->assertOk();

    $this->postJson("/api/games/{$session->uuid}/move", ['position' => 8])->assertForbidden();

    expect(GameResult::query()->where('game_session_id', $session->id)->count())->toBe(1)
        ->and(RewardTransaction::query()->where('user_id', $user->id)->count())->toBe(1);
});

it('awards the opponent a win when a player resigns', function () {
    $first = User::factory()->create();
    $second = User::factory()->create();
    $session = ticTacToeSession($first, [
        'players' => [$first->id, $second->id],
        'marks' => [(string) $first->id => 'X', (string) $second->id => 'O'],
    ], [
        'room_id' => null,
    ]);

    // Give the session a room so the loss is treated as multiplayer scoring.
    $room = App\Models\GameRoom::factory()->create([
        'game_id' => $session->game_id,
        'host_id' => $first->id,
        'status' => 'in_progress',
        'current_session_id' => $session->id,
    ]);
    $session->update(['room_id' => $room->id]);

    Sanctum::actingAs($first);

    $this->postJson("/api/sessions/{$session->uuid}/resign")
        ->assertOk()
        ->assertJsonPath('data.status', 'finished')
        ->assertJsonPath('data.winner_id', $second->id);

    $winnerResult = GameResult::query()->where('game_session_id', $session->id)->where('user_id', $second->id)->sole();
    $loserResult = GameResult::query()->where('game_session_id', $session->id)->where('user_id', $first->id)->sole();

    expect($winnerResult->result)->toBe(GameResultType::Win)
        ->and($winnerResult->is_multiplayer)->toBeTrue()
        ->and($loserResult->result)->toBe(GameResultType::Loss);

    expect(PlayerStatistics::query()->where('user_id', $second->id)->sole()->points)->toBe(100);
});

it('finishes a number guess game when the secret is guessed', function () {
    $game = createGame('number-guess');
    $user = User::factory()->create();
    $session = GameSession::factory()->create([
        'game_id' => $game->id,
        'created_by' => $user->id,
        'difficulty' => 'medium',
        'state' => [
            'players' => [$user->id],
            'min' => 1,
            'max' => 100,
            'secret' => 42,
            'max_attempts' => 7,
            'attempts' => [],
            'won' => false,
        ],
        'current_turn_user_id' => $user->id,
    ]);
    Sanctum::actingAs($user);

    $this->postJson("/api/games/{$session->uuid}/move", ['guess' => 42])
        ->assertOk()
        ->assertJsonPath('data.status', 'finished')
        ->assertJsonPath('data.winner_id', $user->id)
        ->assertJsonPath('data.state.won', true);

    expect(PlayerStatistics::query()->where('user_id', $user->id)->sole()->points)->toBe(150);
});

it('resolves a multiplayer rock paper scissors round once both players choose', function () {
    $game = createGame('rock-paper-scissors');
    $first = User::factory()->create();
    $second = User::factory()->create();
    $session = GameSession::factory()->create([
        'game_id' => $game->id,
        'created_by' => $first->id,
        'difficulty' => 'medium',
        'state' => [
            'players' => [$first->id, $second->id],
            'round' => 1,
            'round_limit' => 5,
            'wins_needed' => 3,
            'scores' => [(string) $first->id => 0, (string) $second->id => 0],
            'ai_score' => 0,
            'choices' => [],
            'history' => [],
        ],
        'current_turn_user_id' => $first->id,
    ]);

    Sanctum::actingAs($first);

    $this->postJson("/api/games/{$session->uuid}/move", ['choice' => 'rock'])
        ->assertOk()
        ->assertJsonPath('data.status', 'active');

    Sanctum::actingAs($second);

    $this->postJson("/api/games/{$session->uuid}/move", ['choice' => 'scissors'])
        ->assertOk()
        ->assertJsonPath('data.status', 'active');

    $session->refresh();

    expect($session->state['scores'][(string) $first->id])->toBe(1)
        ->and($session->state['scores'][(string) $second->id])->toBe(0)
        ->and($session->state['round'])->toBe(2);
});

it('returns a paginated game history for the current player only', function () {
    $game = createGame('tic-tac-toe');
    $user = User::factory()->create();
    $other = User::factory()->create();

    foreach ([$user, $other] as $player) {
        $session = GameSession::factory()->create([
            'game_id' => $game->id,
            'created_by' => $player->id,
            'status' => 'finished',
            'state' => ['players' => [$player->id]],
        ]);

        GameResult::create([
            'game_session_id' => $session->id,
            'user_id' => $player->id,
            'game_id' => $game->id,
            'result' => GameResultType::Win,
            'score' => 10,
            'points_awarded' => 100,
        ]);
    }

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/games/tic-tac-toe/history');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.game_id', $game->id)
        ->assertJsonStructure(['meta' => ['current_page', 'last_page', 'per_page', 'total']]);
});

it('hides the secret number while a number guess game is active', function () {
    $game = createGame('number-guess');
    $user = User::factory()->create();
    $session = GameSession::factory()->create([
        'game_id' => $game->id,
        'created_by' => $user->id,
        'difficulty' => 'medium',
        'state' => [
            'players' => [$user->id],
            'min' => 1,
            'max' => 100,
            'secret' => 42,
            'max_attempts' => 7,
            'attempts' => [],
            'won' => false,
        ],
        'current_turn_user_id' => $user->id,
    ]);
    Sanctum::actingAs($user);

    $response = $this->getJson("/api/sessions/{$session->uuid}");

    $response->assertOk();
    expect($response->json('data.state.secret'))->toBeNull();
});
