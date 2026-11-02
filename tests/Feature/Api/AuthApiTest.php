<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('registers a new player and returns an api token', function () {
    $response = $this->postJson('/api/register', [
        'name' => 'New Player',
        'username' => 'new_player',
        'email' => 'new@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'data' => [
                'user' => ['id', 'name', 'username', 'email'],
                'token',
            ],
        ]);

    $user = User::query()->where('email', 'new@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->username)->toBe('new_player')
        ->and($user->statistics)->not->toBeNull()
        ->and($user->tokens()->count())->toBe(1);
});

it('rejects duplicate usernames during registration', function () {
    User::factory()->create(['username' => 'taken_tag']);

    $this->postJson('/api/register', [
        'name' => 'Another Player',
        'username' => 'taken_tag',
        'email' => 'another@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('username');
});

it('logs a player in with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'player@example.com',
        'password' => 'password',
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'player@example.com',
        'password' => 'password',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.user.id', $user->id)
        ->assertJsonStructure(['data' => ['token']]);

    expect($user->tokens()->count())->toBe(1);
});

it('rejects invalid credentials', function () {
    User::factory()->create([
        'email' => 'player@example.com',
        'password' => 'password',
    ]);

    $this->postJson('/api/login', [
        'email' => 'player@example.com',
        'password' => 'wrong-password',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('email');
});

it('blocks suspended accounts from logging in', function () {
    User::factory()->suspended()->create([
        'email' => 'banned@example.com',
        'password' => 'password',
    ]);

    $this->postJson('/api/login', [
        'email' => 'banned@example.com',
        'password' => 'password',
    ])->assertForbidden();
});

it('requires authentication for protected endpoints', function () {
    $this->getJson('/api/user')->assertUnauthorized();
    $this->getJson('/api/games')->assertUnauthorized();
    $this->getJson('/api/leaderboard')->assertUnauthorized();
});

it('returns the authenticated user profile', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.username', $user->username);
});

it('revokes the token on logout', function () {
    $user = User::factory()->create();
    $token = $user->createToken('api')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/logout')
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($user->tokens()->count())->toBe(0);
});

it('returns suspended players an error on protected endpoints', function () {
    $user = User::factory()->suspended()->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/user')->assertForbidden();
});
