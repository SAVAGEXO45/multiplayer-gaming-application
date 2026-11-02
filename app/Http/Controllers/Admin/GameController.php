<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateGameRequest;
use App\Models\Game;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class GameController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/Games', [
            'games' => Game::query()
                ->withCount(['results', 'sessions', 'rooms'])
                ->orderBy('sort_order')
                ->get()
                ->map(fn (Game $game): array => [
                    'id' => $game->id,
                    'slug' => $game->slug,
                    'name' => $game->name,
                    'tagline' => $game->tagline,
                    'icon' => $game->icon,
                    'accent' => $game->accent,
                    'is_active' => $game->is_active,
                    'supports_multiplayer' => $game->supports_multiplayer,
                    'min_players' => $game->min_players,
                    'max_players' => $game->max_players,
                    'points_win' => $game->points_win,
                    'points_draw' => $game->points_draw,
                    'points_loss' => $game->points_loss,
                    'points_participation' => $game->points_participation,
                    'results_count' => $game->results_count,
                    'sessions_count' => $game->sessions_count,
                    'rooms_count' => $game->rooms_count,
                ]),
        ]);
    }

    public function update(UpdateGameRequest $request, Game $game): RedirectResponse
    {
        $game->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$game->name} updated."]);

        return back();
    }
}
