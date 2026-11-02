<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateGameRequest;
use App\Http\Requests\Admin\UpdateUserRoleRequest;
use App\Http\Requests\Admin\UpdateUserStatusRequest;
use App\Http\Resources\GameResource;
use App\Http\Resources\GameRoomResource;
use App\Http\Resources\SecurityEventResource;
use App\Http\Resources\UserResource;
use App\Models\Game;
use App\Models\GameResult;
use App\Models\GameRoom;
use App\Models\PlayerStatistics;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Services\SecurityEventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    use ApiResponse;

    public function dashboard(): JsonResponse
    {
        return $this->success([
            'counts' => [
                'users' => User::query()->count(),
                'players' => User::query()->where('role', 'player')->count(),
                'suspended' => User::query()->where('is_suspended', true)->count(),
                'games_played' => GameResult::query()->count(),
                'active_rooms' => GameRoom::query()->where('status', 'in_progress')->count(),
                'waiting_rooms' => GameRoom::query()->where('status', 'waiting')->count(),
                'total_points' => (int) PlayerStatistics::query()->sum('points'),
            ],
            'recent_results' => GameResult::query()
                ->with(['user', 'game'])
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn (GameResult $result): array => [
                    'id' => $result->id,
                    'user' => $result->user->username,
                    'game' => $result->game->name,
                    'result' => $result->result->value,
                    'points_awarded' => $result->points_awarded,
                    'is_multiplayer' => $result->is_multiplayer,
                    'created_at' => $result->created_at?->toIso8601String(),
                ]),
            'suspicious' => SecurityEventResource::collection(
                SecurityEvent::query()->with('user')->latest()->limit(10)->get()
            ),
        ]);
    }

    public function users(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search'));

        $users = User::query()
            ->with('statistics')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate((int) min(50, max(1, (int) $request->query('per_page', 20))));

        return $this->success(
            UserResource::collection($users->items()),
            null,
            200,
            [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        );
    }

    public function games(): JsonResponse
    {
        return $this->success(GameResource::collection(Game::query()->orderBy('sort_order')->get()));
    }

    public function rooms(Request $request): JsonResponse
    {
        $rooms = GameRoom::query()
            ->with(['game', 'host', 'activePlayers.user'])
            ->latest()
            ->paginate((int) min(50, max(1, (int) $request->query('per_page', 20))));

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

    public function activity(): JsonResponse
    {
        return $this->success(SecurityEventResource::collection(
            SecurityEvent::query()->with('user')->latest()->paginate(20)->items()
        ));
    }

    public function updateUserStatus(
        UpdateUserStatusRequest $request,
        User $user,
        SecurityEventService $security,
    ): JsonResponse {
        if ($request->user()->id === $user->id) {
            return $this->error('You cannot change your own account status.', 422);
        }

        DB::transaction(function () use ($request, $user, $security): void {
            if ($request->validated('action') === 'suspend') {
                $user->update([
                    'is_suspended' => true,
                    'suspended_at' => now(),
                    'suspension_reason' => $request->validated('reason'),
                ]);

                $user->tokens()->delete();

                $security->log(
                    'admin.suspend_user',
                    "Administrator {$request->user()->username} suspended {$user->username}.",
                    $user,
                    'high',
                    ['reason' => $request->validated('reason')],
                    $request,
                );
            } else {
                $user->update([
                    'is_suspended' => false,
                    'suspended_at' => null,
                    'suspension_reason' => null,
                ]);

                $security->log(
                    'admin.reactivate_user',
                    "Administrator {$request->user()->username} reactivated {$user->username}.",
                    $user,
                    'medium',
                    [],
                    $request,
                );
            }
        });

        return $this->success(new UserResource($user->fresh('statistics')), 'Account status updated.');
    }

    public function updateUserRole(UpdateUserRoleRequest $request, User $user, SecurityEventService $security): JsonResponse
    {
        if ($request->user()->id === $user->id) {
            return $this->error('You cannot change your own role.', 422);
        }

        $previous = $user->role->value;
        $user->update(['role' => $request->validated('role')]);

        $security->log(
            'admin.role_change',
            "Administrator {$request->user()->username} changed {$user->username} from {$previous} to {$user->role->value}.",
            $user,
            'high',
            [],
            $request,
        );

        return $this->success(new UserResource($user->fresh('statistics')), 'User role updated.');
    }

    public function updateGame(UpdateGameRequest $request, Game $game): JsonResponse
    {
        $game->update($request->validated());

        return $this->success(new GameResource($game->fresh()), 'Game updated.');
    }
}
