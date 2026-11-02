<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Services\LeaderboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    use ApiResponse;

    public function index(Request $request, LeaderboardService $leaderboard): JsonResponse
    {
        $period = in_array($request->query('period'), ['all', 'weekly', 'monthly'], true)
            ? $request->query('period')
            : 'all';

        $game = $this->gameFromRequest($request);

        return $this->success(
            $leaderboard->forPeriod($period, $game, $this->limit($request)),
            null,
            200,
            ['period' => $period, 'game' => $game?->slug],
        );
    }

    public function weekly(Request $request, LeaderboardService $leaderboard): JsonResponse
    {
        return $this->success(
            $leaderboard->forPeriod('weekly', $this->gameFromRequest($request), $this->limit($request)),
            null,
            200,
            ['period' => 'weekly'],
        );
    }

    public function monthly(Request $request, LeaderboardService $leaderboard): JsonResponse
    {
        return $this->success(
            $leaderboard->forPeriod('monthly', $this->gameFromRequest($request), $this->limit($request)),
            null,
            200,
            ['period' => 'monthly'],
        );
    }

    private function gameFromRequest(Request $request): ?Game
    {
        $slug = $request->query('game');

        return is_string($slug) && $slug !== ''
            ? Game::query()->where('slug', $slug)->first()
            : null;
    }

    private function limit(Request $request): int
    {
        return (int) min(100, max(1, (int) $request->query('limit', 50)));
    }
}
