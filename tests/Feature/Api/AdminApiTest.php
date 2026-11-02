<?php

use App\Models\SecurityEvent;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('blocks non administrators from the admin dashboard', function () {
    $player = User::factory()->create();
    Sanctum::actingAs($player);

    $this->getJson('/api/admin/dashboard')->assertForbidden();
});

it('allows administrators to read the dashboard', function () {
    $admin = User::factory()->admin()->create();
    Sanctum::actingAs($admin);

    $this->getJson('/api/admin/dashboard')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data' => ['counts', 'recent_results', 'suspicious']]);
});

it('lets administrators suspend and reactivate a player', function () {
    $admin = User::factory()->admin()->create();
    $player = User::factory()->create();
    Sanctum::actingAs($admin);

    $this->patchJson("/api/admin/users/{$player->id}/status", [
        'action' => 'suspend',
        'reason' => 'Cheating in ranked matches.',
    ])->assertOk()
        ->assertJsonPath('data.is_suspended', true);

    expect($player->fresh()->is_suspended)->toBeTrue()
        ->and($player->fresh()->suspension_reason)->toBe('Cheating in ranked matches.');

    expect(SecurityEvent::query()->where('type', 'admin.suspend_user')->exists())->toBeTrue();

    $this->patchJson("/api/admin/users/{$player->id}/status", [
        'action' => 'reactivate',
    ])->assertOk()
        ->assertJsonPath('data.is_suspended', false);

    expect($player->fresh()->is_suspended)->toBeFalse();
});

it('prevents administrators from changing their own status', function () {
    $admin = User::factory()->admin()->create();
    Sanctum::actingAs($admin);

    $this->patchJson("/api/admin/users/{$admin->id}/status", [
        'action' => 'suspend',
        'reason' => 'Testing self suspension.',
    ])->assertUnprocessable();
});

it('lets administrators promote and demote players', function () {
    $admin = User::factory()->admin()->create();
    $player = User::factory()->create();
    Sanctum::actingAs($admin);

    $this->patchJson("/api/admin/users/{$player->id}/role", ['role' => 'admin'])
        ->assertOk()
        ->assertJsonPath('data.role', 'admin');

    expect($player->fresh()->isAdmin())->toBeTrue();

    $this->patchJson("/api/admin/users/{$player->id}/role", ['role' => 'player'])
        ->assertOk();

    expect($player->fresh()->isAdmin())->toBeFalse();
});

it('prevents administrators from changing their own role', function () {
    $admin = User::factory()->admin()->create();
    Sanctum::actingAs($admin);

    $this->patchJson("/api/admin/users/{$admin->id}/role", ['role' => 'player'])
        ->assertUnprocessable();
});

it('lets administrators update the game economy', function () {
    $game = createGame('tic-tac-toe');
    $admin = User::factory()->admin()->create();
    Sanctum::actingAs($admin);

    $this->patchJson("/api/admin/games/{$game->id}", [
        'points_win' => 250,
        'points_draw' => 75,
        'is_active' => false,
    ])->assertOk()
        ->assertJsonPath('data.points_win', 250)
        ->assertJsonPath('data.is_active', false);

    $game->refresh();

    expect($game->points_win)->toBe(250)
        ->and($game->points_draw)->toBe(75)
        ->and($game->is_active)->toBeFalse();
});

it('validates game economy updates', function () {
    $game = createGame('tic-tac-toe');
    $admin = User::factory()->admin()->create();
    Sanctum::actingAs($admin);

    $this->patchJson("/api/admin/games/{$game->id}", [
        'points_win' => -100,
        'points_loss' => 500,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['points_win', 'points_loss']);
});

it('protects the web admin area with middleware', function () {
    $player = User::factory()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($player)->get('/admin')->assertForbidden();
    $this->actingAs($player)->get('/admin/users')->assertForbidden();

    $this->actingAs($admin)->get('/admin')->assertOk();
    $this->actingAs($admin)->get('/admin/users')->assertOk();
    $this->actingAs($admin)->get('/admin/games')->assertOk();
    $this->actingAs($admin)->get('/admin/rooms')->assertOk();
    $this->actingAs($admin)->get('/admin/rewards')->assertOk();
    $this->actingAs($admin)->get('/admin/activity')->assertOk();
});

it('redirects guests away from the admin area', function () {
    $this->get('/admin')->assertRedirect(route('login'));
});

it('logs administrators out when a suspended account hits a web route', function () {
    $player = User::factory()->suspended()->create();

    $this->actingAs($player)->get('/dashboard')->assertRedirect(route('login'));
});
