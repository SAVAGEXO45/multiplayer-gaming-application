<?php

namespace Tests\Unit;

use App\Models\GameSession;
use App\Services\Games\ActionArenaEngine;
use App\Services\Games\GameEngineFactory;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class GameEnginesTest extends TestCase
{
    private function makePlayers(): array
    {
        return [
            ['user_id' => 101, 'slot' => 1, 'symbol' => 'P1'],
            ['user_id' => 202, 'slot' => 2, 'symbol' => 'P2'],
        ];
    }

    public function test_factory_creates_action_arena_engines_for_all_10_slugs(): void
    {
        $actionSlugs = [
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

        foreach ($actionSlugs as $slug) {
            $engine = GameEngineFactory::make($slug);
            $this->assertInstanceOf(ActionArenaEngine::class, $engine);
        }
    }

    public function test_factory_throws_exception_for_unsupported_engine(): void
    {
        $this->expectException(InvalidArgumentException::class);
        GameEngineFactory::make('unsupported-game');
    }

    public function test_action_arena_engine_initializes_player_runs_and_board_state(): void
    {
        $engine = new ActionArenaEngine('cyber-strike-fps');
        $players = $this->makePlayers();
        $board = $engine->initializeBoard($players);

        $this->assertSame('action_arena', $board['engine']);
        $this->assertSame('cyber-strike-fps', $board['game_slug']);
        $this->assertArrayHasKey(101, $board['player_runs']);
        $this->assertArrayHasKey(202, $board['player_runs']);
        $this->assertSame(0, $board['player_runs'][101]['score']);
        $this->assertFalse($board['player_runs'][101]['finished']);
    }

    public function test_action_arena_engine_applies_score_updates_and_determines_winner(): void
    {
        $engine = new ActionArenaEngine('nitro-apex-gt');
        $players = $this->makePlayers();
        $board = $engine->initializeBoard($players);

        $session = new GameSession([
            'status' => 'in_progress',
            'board_state' => $board,
        ]);

        // P1 submits intermediate score
        $res1 = $engine->applyMove($session, 101, ['score' => 1850, 'level' => 3, 'finished' => false], $players);
        $this->assertSame('in_progress', $res1['status']);
        $this->assertNull($res1['winner_user_id']);
        $session->board_state = $res1['board_state'];

        // P1 finishes run with 3400 pts
        $res2 = $engine->applyMove($session, 101, ['score' => 3400, 'level' => 5, 'finished' => true], $players);
        $this->assertSame('in_progress', $res2['status']);
        $session->board_state = $res2['board_state'];

        // P2 finishes run with 2800 pts -> all finished, P1 wins!
        $res3 = $engine->applyMove($session, 202, ['score' => 2800, 'level' => 4, 'finished' => true], $players);
        $this->assertSame('completed', $res3['status']);
        $this->assertSame(101, $res3['winner_user_id']);
        $this->assertFalse($res3['is_draw']);
        $this->assertStringContainsString('3400 pts', $res3['result_reason']);
    }

    public function test_action_arena_engine_detects_draw_when_scores_tie(): void
    {
        $engine = new ActionArenaEngine('shadow-dojo-brawl');
        $players = $this->makePlayers();
        $board = $engine->initializeBoard($players);

        $session = new GameSession([
            'status' => 'in_progress',
            'board_state' => $board,
        ]);

        $res1 = $engine->applyMove($session, 101, ['score' => 2500, 'finished' => true], $players);
        $session->board_state = $res1['board_state'];

        $res2 = $engine->applyMove($session, 202, ['score' => 2500, 'finished' => true], $players);
        $this->assertSame('completed', $res2['status']);
        $this->assertTrue($res2['is_draw']);
        $this->assertNull($res2['winner_user_id']);
    }

    public function test_action_arena_engine_rejects_outsider(): void
    {
        $engine = new ActionArenaEngine('cyber-strike-fps');
        $players = $this->makePlayers();
        $board = $engine->initializeBoard($players);

        $session = new GameSession([
            'status' => 'in_progress',
            'board_state' => $board,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $engine->applyMove($session, 999, ['score' => 500], $players);
    }
}
