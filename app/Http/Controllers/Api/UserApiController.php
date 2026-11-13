<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));

        $users = User::query()
            ->with('globalStatistic')
            ->where('is_suspended', false)
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('username', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('is_online')
            ->limit(25)
            ->get();

        return response()->json([
            'success' => true,
            'data' => UserResource::collection($users),
        ]);
    }

    public function show(string $username): JsonResponse
    {
        $user = User::with(['globalStatistic', 'statistics.game', 'achievements'])
            ->where('username', strtolower($username))
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => new UserResource($user),
                'game_statistics' => $user->statistics->whereNotNull('game_id')->values(),
                'achievements' => $user->achievements,
            ],
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->safe()->only(['name', 'username', 'bio']);
        $data['username'] = strtolower($data['username']);

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'data' => new UserResource($user->fresh('globalStatistic')),
        ]);
    }
}
