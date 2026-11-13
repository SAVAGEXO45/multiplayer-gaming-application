<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LeaderboardResource;
use App\Models\Game;
use App\Models\Leaderboard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaderboardApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $period = $request->query('period', 'global');
        if (!in_array($period, ['global', 'weekly', 'monthly'], true)) {
            $period = 'global';
        }

        $periodKey = match ($period) {
            'weekly' => now()->format('o-\WW'),
            'monthly' => now()->format('Y-m'),
            default => 'all',
        };

        $gameId = null;
        if ($request->filled('game')) {
            $game = Game::where('slug', $request->query('game'))->first();
            $gameId = $game?->id;
        }

        $entries = Leaderboard::with(['user', 'game'])
            ->where('period_type', $period)
            ->where('period_key', $periodKey)
            ->when($gameId === null, fn ($q) => $q->whereNull('game_id'), fn ($q) => $q->where('game_id', $gameId))
            ->orderByDesc('points')
            ->orderByDesc('wins')
            ->orderByDesc('win_percentage')
            ->limit(50)
            ->get();

        // Ensure dynamic rank index if not yet persisted
        $entries->each(function ($entry, $idx) {
            $entry->rank = $idx + 1;
        });

        return response()->json([
            'success' => true,
            'meta' => [
                'period_type' => $period,
                'period_key' => $periodKey,
                'game_slug' => $request->query('game'),
            ],
            'data' => LeaderboardResource::collection($entries),
        ]);
    }
}
