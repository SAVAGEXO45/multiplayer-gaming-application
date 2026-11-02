<?php

namespace App\Http\Controllers;

use App\Http\Requests\GameMoveRequest;
use App\Http\Resources\GameSessionResource;
use App\Models\GameSession;
use App\Services\GameService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GameSessionController extends Controller
{
    public function show(Request $request, GameSession $session): Response
    {
        $this->authorize('view', $session);

        $session->load(['game', 'room', 'results.user', 'currentTurn', 'winner']);

        return Inertia::render('games/Play', [
            'session' => new GameSessionResource($session),
            'game' => [
                'slug' => $session->game->slug,
                'name' => $session->game->name,
                'icon' => $session->game->icon,
                'accent' => $session->game->accent,
                'instructions' => $session->game->instructions,
                'points_win' => $session->game->points_win,
            ],
            'roomCode' => $session->room?->code,
        ]);
    }

    public function move(GameMoveRequest $request, GameSession $session, GameService $games): RedirectResponse
    {
        $this->authorize('move', $session);

        $updated = $games->submitMove($session, $request->user(), $request->validated());

        $message = $updated->isActive() ? 'Move submitted.' : 'Game finished. Points have been awarded.';

        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
    }

    public function resign(Request $request, GameSession $session, GameService $games): RedirectResponse
    {
        $this->authorize('resign', $session);

        $games->forfeit($session, $request->user());

        Inertia::flash('toast', ['type' => 'info', 'message' => 'You resigned from the match.']);

        return back();
    }

    public function state(Request $request, GameSession $session): JsonResponse
    {
        $this->authorize('view', $session);

        $session->load(['game', 'room', 'results.user', 'currentTurn', 'winner']);

        return response()->json([
            'success' => true,
            'data' => new GameSessionResource($session),
        ]);
    }
}
