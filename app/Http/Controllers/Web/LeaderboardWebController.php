<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\Leaderboard;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaderboardWebController extends Controller
{
    public function index(Request $request): View
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

        $games = Game::where('is_active', true)->get();
        $selectedGameSlug = $request->query('game');
        $selectedGame = $selectedGameSlug ? $games->firstWhere('slug', $selectedGameSlug) : null;

        $entries = Leaderboard::with('user')
            ->where('period_type', $period)
            ->where('period_key', $periodKey)
            ->when($selectedGame === null, fn ($q) => $q->whereNull('game_id'), fn ($q) => $q->where('game_id', $selectedGame->id))
            ->orderByDesc('points')
            ->orderByDesc('wins')
            ->orderByDesc('win_percentage')
            ->limit(50)
            ->get();

        $entries->each(function ($entry, $idx) {
            $entry->rank = $idx + 1;
        });

        return view('leaderboards.index', compact(
            'entries',
            'games',
            'period',
            'periodKey',
            'selectedGame'
        ));
    }
}
