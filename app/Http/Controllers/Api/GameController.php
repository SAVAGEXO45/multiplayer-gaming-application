<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\GameMoveRequest;
use App\Http\Requests\StartGameRequest;
use App\Http\Resources\GameResource;
use App\Http\Resources\GameResultResource;
use App\Http\Resources\GameSessionResource;
use App\Models\Game;
use App\Models\GameResult;
use App\Models\GameSession;
use App\Services\GameService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GameController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $games = Game::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return $this->success(GameResource::collection($games));
    }

    public function show(Game $game): JsonResponse
    {
        abort_unless($game->is_active, 404);

        return $this->success(new GameResource($game));
    }

    public function start(StartGameRequest $request, Game $game, GameService $games): JsonResponse
    {
        $session = $games->startSolo($request->user(), $game, $request->validated('difficulty'));

        return $this->success(
            new GameSessionResource($session->load(['game', 'results.user'])),
            'Game started.',
            201,
        );
    }

    public function session(Request $request, GameSession $session): JsonResponse
    {
        $this->authorize('view', $session);

        $session->load(['game', 'room', 'results.user', 'currentTurn', 'winner']);

        return $this->success(new GameSessionResource($session));
    }

    public function move(GameMoveRequest $request, GameSession $session, GameService $games): JsonResponse
    {
        $this->authorize('move', $session);

        $updated = $games->submitMove($session, $request->user(), $request->validated());

        return $this->success(
            new GameSessionResource($updated->load(['game', 'room', 'results.user'])),
            $updated->isActive() ? 'Move accepted.' : 'Game finished.',
        );
    }

    public function resign(Request $request, GameSession $session, GameService $games): JsonResponse
    {
        $this->authorize('resign', $session);

        $updated = $games->forfeit($session, $request->user());

        return $this->success(
            new GameSessionResource($updated->load(['game', 'room', 'results.user'])),
            'You resigned from the match.',
        );
    }

    public function history(Request $request, Game $game): JsonResponse
    {
        $history = GameResult::query()
            ->where('user_id', $request->user()->id)
            ->where('game_id', $game->id)
            ->with(['game', 'session.results.user'])
            ->latest()
            ->paginate((int) min(50, max(1, (int) $request->query('per_page', 15))));

        return $this->success(
            GameResultResource::collection($history->items()),
            null,
            200,
            [
                'current_page' => $history->currentPage(),
                'last_page' => $history->lastPage(),
                'per_page' => $history->perPage(),
                'total' => $history->total(),
            ],
        );
    }
}
