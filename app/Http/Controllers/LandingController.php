<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\GameResult;
use App\Models\User;
use App\Services\LeaderboardService;
use Inertia\Inertia;
use Inertia\Response;

class LandingController extends Controller
{
    public function index(LeaderboardService $leaderboard): Response
    {
        return Inertia::render('Welcome', [
            'games' => Game::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->map(fn (Game $game): array => [
                    'id' => $game->id,
                    'slug' => $game->slug,
                    'name' => $game->name,
                    'tagline' => $game->tagline,
                    'description' => $game->description,
                    'icon' => $game->icon,
                    'accent' => $game->accent,
                    'supports_multiplayer' => $game->supports_multiplayer,
                    'points_win' => $game->points_win,
                ]),
            'topPlayers' => $leaderboard->global(5),
            'stats' => [
                'players' => User::query()->where('is_suspended', false)->count(),
                'matches' => GameResult::query()->count(),
                'matches_today' => GameResult::query()->whereDate('created_at', today())->count(),
            ],
        ]);
    }
}
