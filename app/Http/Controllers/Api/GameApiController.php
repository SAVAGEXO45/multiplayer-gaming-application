<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GameResource;
use App\Http\Resources\GameRoomResource;
use App\Models\Game;
use Illuminate\Http\JsonResponse;

class GameApiController extends Controller
{
    public function index(): JsonResponse
    {
        $games = Game::where('is_active', true)
            ->withCount('activeRooms')
            ->orderBy('id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => GameResource::collection($games),
        ]);
    }

    public function show(Game $game): JsonResponse
    {
        $game->loadCount('activeRooms');
        $publicRooms = $game->rooms()
            ->with(['host', 'roomPlayers.user'])
            ->where('visibility', 'public')
            ->whereIn('status', ['waiting', 'in_progress'])
            ->latest()
            ->limit(15)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'game' => new GameResource($game),
                'public_rooms' => GameRoomResource::collection($publicRooms),
            ],
        ]);
    }
}
