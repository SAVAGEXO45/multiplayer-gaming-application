<?php

use App\Enums\RoomStatus;
use App\Models\GameRoom;
use App\Models\RoomPlayer;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

function createRoomFor(User $host, array $attributes = []): GameRoom
{
    $game = createGame('tic-tac-toe');

    $room = GameRoom::factory()->create(array_merge([
        'game_id' => $game->id,
        'host_id' => $host->id,
        'max_players' => 2,
        'status' => RoomStatus::Waiting,
        'is_private' => false,
        'password' => null,
    ], $attributes));

    RoomPlayer::factory()->create([
        'game_room_id' => $room->id,
        'user_id' => $host->id,
        'is_host' => true,
        'is_ready' => false,
        'joined_at' => now(),
    ]);

    return $room->fresh(['game', 'host', 'activePlayers.user']);
}

it('creates a room with a unique code and the host as a player', function () {
    $game = createGame('tic-tac-toe');
    $host = User::factory()->create();
    Sanctum::actingAs($host);

    $response = $this->postJson('/api/rooms', [
        'game_id' => $game->id,
        'name' => 'Friday Night Duel',
        'max_players' => 2,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.status', 'waiting')
        ->assertJsonPath('data.host.id', $host->id)
        ->assertJsonCount(1, 'data.players');

    $room = GameRoom::query()->sole();

    expect(strlen($room->code))->toBe(6)
        ->and($room->activePlayers()->count())->toBe(1)
        ->and($room->activePlayers()->first()->is_host)->toBeTrue();
});

it('generates different codes for different rooms', function () {
    $game = createGame('tic-tac-toe');
    $host = User::factory()->create();
    Sanctum::actingAs($host);

    foreach (['Room one', 'Room two', 'Room three'] as $name) {
        $this->postJson('/api/rooms', [
            'game_id' => $game->id,
            'name' => $name,
            'max_players' => 2,
        ])->assertCreated();
    }

    $codes = GameRoom::query()->pluck('code');

    expect($codes->count())->toBe(3)
        ->and($codes->unique()->count())->toBe(3);
});

it('rejects rooms for games that do not support multiplayer', function () {
    $game = createGame('number-guess');
    $host = User::factory()->create();
    Sanctum::actingAs($host);

    $this->postJson('/api/rooms', [
        'game_id' => $game->id,
        'name' => 'Solo only',
        'max_players' => 2,
    ])->assertUnprocessable();
});

it('rejects room capacities outside the game limits', function () {
    $game = createGame('tic-tac-toe');
    $host = User::factory()->create();
    Sanctum::actingAs($host);

    $this->postJson('/api/rooms', [
        'game_id' => $game->id,
        'name' => 'Too many players',
        'max_players' => 5,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('max_players');
});

it('requires a password for private rooms', function () {
    $game = createGame('tic-tac-toe');
    $host = User::factory()->create();
    Sanctum::actingAs($host);

    $this->postJson('/api/rooms', [
        'game_id' => $game->id,
        'name' => 'Secret room',
        'max_players' => 2,
        'is_private' => true,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('password');
});

it('lets a second player join a waiting room', function () {
    $host = User::factory()->create();
    $guest = User::factory()->create();
    $room = createRoomFor($host);
    Sanctum::actingAs($guest);

    $this->postJson("/api/rooms/{$room->code}/join")
        ->assertOk()
        ->assertJsonPath('data.status', 'waiting');

    expect($room->activePlayers()->count())->toBe(2);
});

it('blocks a third player from joining a full room', function () {
    $host = User::factory()->create();
    $guest = User::factory()->create();
    $third = User::factory()->create();
    $room = createRoomFor($host);

    Sanctum::actingAs($guest);
    $this->postJson("/api/rooms/{$room->code}/join")->assertOk();

    Sanctum::actingAs($third);
    $this->postJson("/api/rooms/{$room->code}/join")
        ->assertUnprocessable()
        ->assertJsonValidationErrors('room');

    expect($room->activePlayers()->count())->toBe(2);
});

it('blocks joins once a room is in progress', function () {
    $host = User::factory()->create();
    $guest = User::factory()->create();
    $room = createRoomFor($host, ['status' => RoomStatus::InProgress]);
    Sanctum::actingAs($guest);

    $this->postJson("/api/rooms/{$room->code}/join")
        ->assertUnprocessable()
        ->assertJsonValidationErrors('room');
});

it('enforces the private room password', function () {
    $host = User::factory()->create();
    $guest = User::factory()->create();
    $room = createRoomFor($host, [
        'is_private' => true,
        'password' => bcrypt('open-sesame'),
    ]);
    Sanctum::actingAs($guest);

    $this->postJson("/api/rooms/{$room->code}/join")
        ->assertUnprocessable()
        ->assertJsonValidationErrors('password');

    $this->postJson("/api/rooms/{$room->code}/join", ['password' => 'wrong'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('password');

    $this->postJson("/api/rooms/{$room->code}/join", ['password' => 'open-sesame'])
        ->assertOk();

    expect($room->activePlayers()->count())->toBe(2);
});

it('toggles a players readiness', function () {
    $host = User::factory()->create();
    $room = createRoomFor($host);
    Sanctum::actingAs($host);

    $this->postJson("/api/rooms/{$room->code}/ready")->assertOk();

    expect($room->activePlayers()->where('user_id', $host->id)->sole()->is_ready)->toBeTrue();

    $this->postJson("/api/rooms/{$room->code}/ready")->assertOk();

    expect($room->activePlayers()->where('user_id', $host->id)->sole()->is_ready)->toBeFalse();
});

it('does not let the host start an empty room', function () {
    $host = User::factory()->create();
    $room = createRoomFor($host);
    Sanctum::actingAs($host);

    $this->postJson("/api/rooms/{$room->code}/start")
        ->assertUnprocessable()
        ->assertJsonValidationErrors('room');

    expect($room->fresh()->status)->toBe(RoomStatus::Waiting);
});

it('does not start until every player is ready', function () {
    $host = User::factory()->create();
    $guest = User::factory()->create();
    $room = createRoomFor($host);

    Sanctum::actingAs($guest);
    $this->postJson("/api/rooms/{$room->code}/join")->assertOk();

    Sanctum::actingAs($host);
    $this->postJson("/api/rooms/{$room->code}/start")
        ->assertUnprocessable()
        ->assertJsonValidationErrors('room');

    // Only the host is ready.
    $this->postJson("/api/rooms/{$room->code}/ready")->assertOk();

    $this->postJson("/api/rooms/{$room->code}/start")
        ->assertUnprocessable()
        ->assertJsonValidationErrors('room');
});

it('only the host can start a room', function () {
    $host = User::factory()->create();
    $guest = User::factory()->create();
    $room = createRoomFor($host);

    Sanctum::actingAs($guest);
    $this->postJson("/api/rooms/{$room->code}/join")->assertOk();
    $this->postJson("/api/rooms/{$room->code}/ready")->assertOk();

    $this->postJson("/api/rooms/{$room->code}/start")->assertForbidden();
});

it('starts a full ready room and creates a session', function () {
    $host = User::factory()->create();
    $guest = User::factory()->create();
    $room = createRoomFor($host);

    Sanctum::actingAs($guest);
    $this->postJson("/api/rooms/{$room->code}/join")->assertOk();
    $this->postJson("/api/rooms/{$room->code}/ready")->assertOk();

    Sanctum::actingAs($host);
    $this->postJson("/api/rooms/{$room->code}/ready")->assertOk();

    $this->postJson("/api/rooms/{$room->code}/start")
        ->assertOk()
        ->assertJsonPath('data.status', 'in_progress');

    $room->refresh();

    expect($room->status)->toBe(RoomStatus::InProgress)
        ->and($room->current_session_id)->not->toBeNull();

    $session = $room->currentSession;
    expect($session)->not->toBeNull()
        ->and($session->playerIds())->toEqualCanonicalizing([$host->id, $guest->id]);
});

it('promotes a new host when the host leaves', function () {
    $host = User::factory()->create();
    $guest = User::factory()->create();
    $room = createRoomFor($host);

    Sanctum::actingAs($guest);
    $this->postJson("/api/rooms/{$room->code}/join")->assertOk();

    Sanctum::actingAs($host);
    $this->postJson("/api/rooms/{$room->code}/leave")->assertOk();

    $room->refresh();

    expect($room->host_id)->toBe($guest->id)
        ->and($room->activePlayers()->count())->toBe(1)
        ->and($room->activePlayers()->first()->is_host)->toBeTrue();
});

it('closes a room when the last player leaves', function () {
    $host = User::factory()->create();
    $room = createRoomFor($host);
    Sanctum::actingAs($host);

    $this->postJson("/api/rooms/{$room->code}/leave")->assertOk();

    expect($room->fresh()->status)->toBe(RoomStatus::Closed);
});
