<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Services\LeaderboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaderboardController extends Controller
{
    public function index(Request $request, LeaderboardService $leaderboard): Response
    {
        $period = in_array($request->query('period'), ['all', 'weekly', 'monthly'], true)
            ? $request->query('period')
            : 'all';

        $gameSlug = $request->query('game');
        $game = is_string($gameSlug) && $gameSlug !== ''
            ? Game::query()->where('slug', $gameSlug)->first()
            : null;

        $user = $request->user();

        return Inertia::render('leaderboard/Index', [
            'entries' => $leaderboard->forPeriod($period, $game, 50),
            'period' => $period,
            'game' => $game?->slug,
            'playerRank' => $user === null ? null : $leaderboard->rankOf($user, $period, $game),
            'games' => Game::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(['id', 'slug', 'name', 'icon', 'accent']),
        ]);
    }
}
