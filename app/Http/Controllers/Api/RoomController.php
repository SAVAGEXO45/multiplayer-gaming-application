<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateRoomRequest;
use App\Http\Requests\JoinRoomRequest;
use App\Http\Resources\GameRoomResource;
use App\Models\Game;
use App\Models\GameRoom;
use App\Services\RoomService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $rooms = GameRoom::query()
            ->whereIn('status', ['waiting', 'in_progress'])
            ->with(['game', 'host', 'activePlayers.user'])
            ->latest()
            ->paginate((int) min(50, max(1, (int) $request->query('per_page', 15))));

        return $this->success(
            GameRoomResource::collection($rooms->items()),
            null,
            200,
            [
                'current_page' => $rooms->currentPage(),
                'last_page' => $rooms->lastPage(),
                'per_page' => $rooms->perPage(),
                'total' => $rooms->total(),
            ],
        );
    }

    public function store(CreateRoomRequest $request, RoomService $rooms): JsonResponse
    {
        $game = Game::query()->findOrFail($request->validated('game_id'));

        $room = $rooms->create($request->user(), $game, $request->validated());

        return $this->success(new GameRoomResource($room), 'Room created.', 201);
    }

    public function show(GameRoom $room): JsonResponse
    {
        $this->authorize('view', $room);

        $room->load(['game', 'host', 'activePlayers.user']);

        return $this->success(new GameRoomResource($room));
    }

    public function join(JoinRoomRequest $request, GameRoom $room, RoomService $rooms): JsonResponse
    {
        $room = $rooms->join($request->user(), $room, $request->validated('password'));

        return $this->success(new GameRoomResource($room), 'Joined room.');
    }

    public function leave(Request $request, GameRoom $room, RoomService $rooms): JsonResponse
    {
        $this->authorize('leave', $room);

        $room = $rooms->leave($request->user(), $room);

        return $this->success(new GameRoomResource($room), 'Left room.');
    }

    public function ready(Request $request, GameRoom $room, RoomService $rooms): JsonResponse
    {
        $room = $rooms->toggleReady($request->user(), $room);

        return $this->success(new GameRoomResource($room), 'Readiness updated.');
    }

    public function start(Request $request, GameRoom $room, RoomService $rooms): JsonResponse
    {
        $this->authorize('start', $room);

        $room = $rooms->start($request->user(), $room);

        return $this->success(new GameRoomResource($room), 'Match started.');
    }
}
