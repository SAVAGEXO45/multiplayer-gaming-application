<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Room\MakeMoveRequest;
use App\Http\Resources\GameRoomResource;
use App\Http\Resources\GameSessionResource;
use App\Models\GameRoom;
use App\Models\GameSession;
use App\Services\RoomService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class GameMoveApiController extends Controller
{
    public function __construct(
        private readonly RoomService $roomService
    ) {}

    public function store(MakeMoveRequest $request, GameRoom $room): JsonResponse
    {
        if (!$room->hasPlayer($request->user()->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden: You are not a member of this game room.',
            ], 403);
        }

        try {
            $result = $this->roomService->submitMove($room, $request->user(), $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Move processed.',
                'data' => [
                    'room' => new GameRoomResource($result['room']->load(['game', 'host', 'roomPlayers.user', 'activeSession.moves.user'])),
                    'session' => new GameSessionResource($result['session']->load('moves.user')),
                    'move' => $result['move'],
                ],
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function showSession(Request $request, GameSession $session): JsonResponse
    {
        $session->load(['room', 'game', 'moves.user']);

        if ($session->room->visibility === 'private' && !$session->room->hasPlayer($request->user()->id) && !$request->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to view this private session.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => new GameSessionResource($session),
        ]);
    }
}
