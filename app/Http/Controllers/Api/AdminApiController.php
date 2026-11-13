<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GameResource;
use App\Http\Resources\GameRoomResource;
use App\Http\Resources\UserResource;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\GameRoom;
use App\Models\GameSession;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminApiController extends Controller
{
    public function overview(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'total_users' => User::count(),
                'online_users' => User::where('is_online', true)->count(),
                'suspended_users' => User::where('is_suspended', true)->count(),
                'total_games' => Game::count(),
                'active_rooms' => GameRoom::whereIn('status', ['waiting', 'in_progress'])->count(),
                'total_sessions' => GameSession::count(),
                'completed_sessions' => GameSession::where('status', 'completed')->count(),
                'open_reports' => Report::where('status', 'open')->count(),
            ],
        ]);
    }

    public function users(): JsonResponse
    {
        $users = User::with('globalStatistic')->latest()->limit(50)->get();

        return response()->json([
            'success' => true,
            'data' => UserResource::collection($users),
        ]);
    }

    public function toggleUserSuspension(Request $request, User $user): JsonResponse
    {
        if ($user->id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot suspend your own administrator account.',
            ], 422);
        }

        $user->update([
            'is_suspended' => !$user->is_suspended,
            'is_online' => $user->is_suspended ? $user->is_online : false,
        ]);

        if ($user->is_suspended) {
            $user->tokens()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => $user->is_suspended
                ? "Account {$user->username} has been suspended."
                : "Account {$user->username} has been reactivated.",
            'data' => new UserResource($user),
        ]);
    }

    public function toggleGameStatus(Game $game): JsonResponse
    {
        $game->update(['is_active' => !$game->is_active]);

        return response()->json([
            'success' => true,
            'message' => "Game {$game->name} status updated.",
            'data' => new GameResource($game),
        ]);
    }

    public function activeRooms(): JsonResponse
    {
        $rooms = GameRoom::with(['game', 'host', 'roomPlayers.user'])
            ->whereIn('status', ['waiting', 'in_progress'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => GameRoomResource::collection($rooms),
        ]);
    }

    public function createAchievement(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:40'],
            'badge_color' => ['nullable', 'string', 'max:30'],
            'points_reward' => ['required', 'integer', 'min:10', 'max:5000'],
            'criteria_type' => ['required', 'string', 'in:games_played,games_won,total_points,win_streak,friends_count,game_specific_wins'],
            'criteria_value' => ['required', 'integer', 'min:1'],
            'game_id' => ['nullable', 'exists:games,id'],
        ]);

        $achievement = Achievement::create([
            ...$validated,
            'slug' => Str::slug($validated['name']) . '-' . Str::lower(Str::random(4)),
            'icon' => $validated['icon'] ?? 'trophy',
            'badge_color' => $validated['badge_color'] ?? 'purple',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Achievement created.',
            'data' => $achievement,
        ], 201);
    }
}
