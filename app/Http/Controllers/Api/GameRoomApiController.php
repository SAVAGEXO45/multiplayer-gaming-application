<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Room\CreateRoomRequest;
use App\Http\Resources\GameRoomResource;
use App\Models\Game;
use App\Models\GameRoom;
use App\Models\User;
use App\Services\RoomService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class GameRoomApiController extends Controller
{
    public function __construct(
        private readonly RoomService $roomService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = GameRoom::with(['game', 'host', 'roomPlayers.user', 'activeSession'])
            ->where('visibility', 'public')
            ->whereIn('status', ['waiting', 'in_progress']);

        if ($request->filled('game')) {
            $query->whereHas('game', fn ($q) => $q->where('slug', $request->query('game')));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        return response()->json([
            'success' => true,
            'data' => GameRoomResource::collection($query->latest()->limit(30)->get()),
        ]);
    }

    public function store(CreateRoomRequest $request): JsonResponse
    {
        try {
            $game = Game::where('slug', $request->validated('game_slug'))->firstOrFail();
            $room = $this->roomService->createRoom($request->user(), $game, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Game room created successfully.',
                'data' => new GameRoomResource($room->load(['game', 'host', 'roomPlayers.user', 'activeSession'])),
            ], 201);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function show(Request $request, GameRoom $room): JsonResponse
    {
        if (!Gate::forUser($request->user())->allows('view', $room)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to private room.',
            ], 403);
        }

        $room->load(['game', 'host', 'roomPlayers.user', 'activeSession.moves.user']);

        return response()->json([
            'success' => true,
            'data' => new GameRoomResource($room),
        ]);
    }

    public function joinByCode(Request $request): JsonResponse
    {
        $request->validate([
            'room_code' => ['required', 'string', 'max:12'],
        ]);

        $room = GameRoom::where('room_code', strtoupper(trim($request->input('room_code'))))->first();
        if (!$room) {
            return response()->json([
                'success' => false,
                'message' => 'No room found with that room code.',
            ], 404);
        }

        return $this->join($request, $room);
    }

    public function join(Request $request, GameRoom $room): JsonResponse
    {
        try {
            $updatedRoom = $this->roomService->joinRoom($room, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Joined room successfully.',
                'data' => new GameRoomResource($updatedRoom->load(['game', 'host', 'roomPlayers.user', 'activeSession.moves.user'])),
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function leave(Request $request, GameRoom $room): JsonResponse
    {
        $this->roomService->leaveRoom($room, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'You have left the room.',
        ]);
    }

    public function startOrRestart(Request $request, GameRoom $room): JsonResponse
    {
        try {
            $this->roomService->startNewSession($room, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'New game session started.',
                'data' => new GameRoomResource($room->fresh(['game', 'host', 'roomPlayers.user', 'activeSession.moves.user'])),
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function invite(Request $request, GameRoom $room): JsonResponse
    {
        $request->validate([
            'username' => ['required', 'string', 'exists:users,username'],
        ]);

        if (!$room->hasPlayer($request->user()->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Only room participants can invite players.',
            ], 403);
        }

        try {
            $target = User::where('username', strtolower($request->input('username')))->firstOrFail();
            $this->roomService->invitePlayer($room, $request->user(), $target);

            return response()->json([
                'success' => true,
                'message' => "Invitation sent to {$target->username}.",
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
