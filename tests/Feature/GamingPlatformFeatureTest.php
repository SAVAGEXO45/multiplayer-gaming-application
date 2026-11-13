<?php

namespace Tests\Feature;

use App\Models\Achievement;
use App\Models\Game;
use App\Models\GameRoom;
use App\Models\Leaderboard;
use App\Models\PlayerStatistic;
use App\Models\User;
use App\Models\UserGameActivity;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GamingPlatformFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_user_can_register_and_login_via_web_and_api(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test Striker',
            'username' => 'test_striker',
            'email' => 'striker@nexusarena.gg',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.username', 'test_striker');

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'test_striker',
            'password' => 'password123',
        ]);

        $loginResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['token', 'user']]);
    }

    public function test_room_creation_joining_and_auto_start_for_action_game(): void
    {
        $host = User::factory()->create(['username' => 'host_player']);
        $challenger = User::factory()->create(['username' => 'challenger_player']);

        Sanctum::actingAs($host);

        $createRes = $this->postJson('/api/rooms', [
            'game_slug' => 'cyber-strike-fps',
            'name' => 'Cyber Strike Squad',
            'visibility' => 'public',
        ]);

        $createRes->assertCreated()
            ->assertJsonPath('data.status', 'waiting');

        $roomCode = $createRes->json('data.room_code');
        $this->assertNotEmpty($roomCode);

        // Challenger joins using room code -> auto-starts session
        Sanctum::actingAs($challenger);
        $joinRes = $this->postJson('/api/rooms/join-code', [
            'room_code' => $roomCode,
        ]);

        $joinRes->assertOk()
            ->assertJsonPath('data.status', 'in_progress')
            ->assertJsonPath('data.active_session.status', 'in_progress');
    }

    public function test_unauthorized_private_room_access_and_outsider_move_prevention(): void
    {
        $host = User::factory()->create();
        $guest = User::factory()->create();
        $outsider = User::factory()->create();

        Sanctum::actingAs($host);
        $roomRes = $this->postJson('/api/rooms', [
            'game_slug' => 'nitro-apex-gt',
            'name' => 'Secret Sprint',
            'visibility' => 'private',
        ]);
        $roomCode = $roomRes->json('data.room_code');

        // Outsider cannot view private room without joining
        Sanctum::actingAs($outsider);
        $this->getJson("/api/rooms/{$roomCode}")
            ->assertForbidden();

        // Guest joins private room via code
        Sanctum::actingAs($guest);
        $this->postJson("/api/rooms/{$roomCode}/join")
            ->assertOk();

        // Outsider cannot submit moves in the room
        Sanctum::actingAs($outsider);
        $this->postJson("/api/rooms/{$roomCode}/moves", ['score' => 500])
            ->assertForbidden();
    }

    public function test_solo_arcade_score_submission_updates_activity_statistics_and_leaderboard(): void
    {
        $player = User::factory()->create(['username' => 'speed_racer']);
        $game = Game::where('slug', 'nitro-apex-gt')->firstOrFail();

        $this->actingAs($player);

        $res = $this->postJson("/games/{$game->slug}/score", [
            'score' => 3850,
            'level' => 4,
            'victory' => true,
        ]);

        $res->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('data.high_score', 3850)
            ->assertJsonPath('data.max_level', 4)
            ->assertJsonPath('data.is_new_high_score', true);

        // Verify UserGameActivity created and updated
        $activity = UserGameActivity::where('user_id', $player->id)->where('game_id', $game->id)->first();
        $this->assertNotNull($activity);
        $this->assertSame(3850, $activity->high_score);
        $this->assertSame(4, $activity->max_level);
        $this->assertSame(1, $activity->plays_count);

        // Verify PlayerStatistic updated
        $stat = PlayerStatistic::where('user_id', $player->id)->where('game_id', $game->id)->first();
        $this->assertNotNull($stat);
        $this->assertSame(1, $stat->games_won);
        $this->assertGreaterThan(0, $stat->total_points);

        // Verify Leaderboard entry updated
        $lb = Leaderboard::where('user_id', $player->id)->where('game_id', $game->id)->first();
        $this->assertNotNull($lb);
        $this->assertSame(1, $lb->wins);
    }

    public function test_favorite_game_toggle_and_library_access(): void
    {
        $player = User::factory()->create(['username' => 'arcade_fan']);
        $game = Game::where('slug', 'shadow-dojo-brawl')->firstOrFail();

        $this->actingAs($player);

        // Toggle favorite -> true
        $favRes = $this->postJson("/games/{$game->slug}/favorite");
        $favRes->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('is_favorite', true);

        $this->assertTrue(UserGameActivity::where('user_id', $player->id)->where('game_id', $game->id)->value('is_favorite'));

        // Visit library page -> contains the favorited game
        $this->get('/library')
            ->assertOk()
            ->assertSee($game->name);

        // Toggle favorite -> false
        $unfavRes = $this->postJson("/games/{$game->slug}/favorite");
        $unfavRes->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('is_favorite', false);
    }

    public function test_multiplayer_action_arena_duel_submits_scores_and_completes_match(): void
    {
        $p1 = User::factory()->create(['username' => 'alpha_racer']);
        $p2 = User::factory()->create(['username' => 'beta_racer']);
        $game = Game::where('slug', 'nitro-apex-gt')->firstOrFail();

        Sanctum::actingAs($p1);
        $roomCode = $this->postJson('/api/rooms', [
            'game_slug' => $game->slug,
            'name' => 'Tokyo Drift Duel',
            'visibility' => 'public',
        ])->json('data.room_code');

        Sanctum::actingAs($p2);
        $this->postJson("/api/rooms/{$roomCode}/join")->assertOk();

        // P1 submits finished run score of 4200
        Sanctum::actingAs($p1);
        $p1Move = $this->postJson("/api/rooms/{$roomCode}/moves", [
            'score' => 4200,
            'level' => 5,
            'finished' => true,
        ]);
        $p1Move->assertOk()
            ->assertJsonPath('data.session.status', 'in_progress');

        // P2 submits finished run score of 3500 -> all finished, P1 declared winner
        Sanctum::actingAs($p2);
        $p2Move = $this->postJson("/api/rooms/{$roomCode}/moves", [
            'score' => 3500,
            'level' => 4,
            'finished' => true,
        ]);
        $p2Move->assertOk()
            ->assertJsonPath('data.session.status', 'completed')
            ->assertJsonPath('data.session.winner_user_id', $p1->id);

        $p1Stat = PlayerStatistic::where('user_id', $p1->id)->where('game_id', $game->id)->first();
        $this->assertNotNull($p1Stat);
        $this->assertSame(1, $p1Stat->games_won);
    }

    public function test_admin_permissions_and_role_middleware(): void
    {
        $player = User::factory()->create(['role' => 'player']);
        $admin = User::factory()->admin()->create(['role' => 'admin']);

        // Regular player is forbidden from admin endpoints
        Sanctum::actingAs($player);
        $this->getJson('/api/admin/overview')->assertForbidden();

        // Admin can access overview and suspend a player
        Sanctum::actingAs($admin);
        $this->getJson('/api/admin/overview')
            ->assertOk()
            ->assertJsonPath('success', true);

        $suspendRes = $this->postJson("/api/admin/users/{$player->id}/toggle-suspension");
        $suspendRes->assertOk()
            ->assertJsonPath('data.is_suspended', true);

        // Suspended player is blocked by EnsureAccountIsActive middleware
        Sanctum::actingAs($player->fresh());
        $this->getJson('/api/auth/me')->assertForbidden();
    }

    public function test_all_primary_web_pages_and_10_action_games_render_cleanly(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('PLAY.')
            ->assertSee('COMPETE.')
            ->assertSee('WIN.')
            ->assertSee('Your Ultimate Gaming Destination')
            ->assertSee('Cyber Strike')
            ->assertSee('Nitro Apex GT')
            ->assertSee('Shadow Dojo')
            ->assertSee('ACTION GAMES');

        $this->get('/games')->assertOk();
        $this->get('/games?category=Shooting')->assertOk()->assertSee('Cyber Strike');
        $this->get('/games?sort=trending')->assertOk();

        // Verify all 10 action game show pages render with their canvas stage
        $all10Slugs = [
            'cyber-strike-fps',
            'nitro-apex-gt',
            'shadow-dojo-brawl',
            'emberfall-survivors',
            'deadzone-outbreak',
            'street-hoops-pro',
            'moto-xtreme-trials',
            'nova-vanguard-3d',
            'arena-tag-royale',
            'prism-breaker-overdrive',
        ];

        foreach ($all10Slugs as $slug) {
            $this->get("/games/{$slug}")
                ->assertOk()
                ->assertSee('nexus-arcade-canvas')
                ->assertSee('60 FPS ENGINE')
                ->assertSee('Controls');
        }

        $this->get('/rooms')->assertOk();
        $this->get('/tournaments')->assertOk();
        $this->get('/leaderboards')->assertOk();
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
        $this->get('/forgot-password')->assertOk();

        $viper = User::where('username', 'cyber_viper')->firstOrFail();
        $this->actingAs($viper);

        $this->get('/dashboard')->assertOk()->assertSee('My Game Library');
        $this->get('/library')->assertOk()->assertSee('Personal Vault');
        $this->get('/friends')->assertOk();
        $this->get('/notifications')->assertOk();
        $this->get('/profile/cyber_viper')->assertOk();

        $room = GameRoom::firstOrFail();
        $this->get("/rooms/{$room->room_code}")->assertOk()->assertSee('nexus-room-arcade-canvas');

        $admin = User::where('username', 'nexus_admin')->firstOrFail();
        $this->actingAs($admin);
        $this->get('/admin')->assertOk();
    }
}
