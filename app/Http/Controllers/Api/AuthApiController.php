<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\PlayerStatistic;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthApiController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->validated('name'),
            'username' => strtolower($request->validated('username')),
            'email' => strtolower($request->validated('email')),
            'password' => Hash::make($request->validated('password')),
            'role' => 'player',
            'is_online' => true,
            'last_seen_at' => now(),
            'email_verified_at' => now(),
        ]);

        PlayerStatistic::create([
            'user_id' => $user->id,
            'game_id' => null,
            'games_played' => 0,
            'games_won' => 0,
            'games_lost' => 0,
            'games_drawn' => 0,
            'win_percentage' => 0,
            'total_points' => 0,
        ]);

        $this->notificationService->notify(
            $user,
            'system',
            'Welcome to Nexus Arena!',
            'Your account has been created. Browse the Games Library or join an active multiplayer room to start competing!'
        );

        $token = $user->createToken('nexus-api-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registration successful.',
            'data' => [
                'user' => new UserResource($user->load('globalStatistic')),
                'token' => $token,
            ],
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $loginField = filter_var($request->input('email'), FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $user = User::where($loginField, strtolower($request->input('email')))->first();

        if (!$user || !Hash::check($request->input('password'), $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials provided.',
            ], 401);
        }

        if ($user->is_suspended) {
            return response()->json([
                'success' => false,
                'message' => 'Your account has been suspended by an administrator.',
            ], 403);
        }

        $user->update([
            'is_online' => true,
            'last_seen_at' => now(),
        ]);

        $token = $user->createToken('nexus-api-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data' => [
                'user' => new UserResource($user->load('globalStatistic')),
                'token' => $token,
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new UserResource($request->user()->load('globalStatistic')),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user) {
            $user->update(['is_online' => false, 'last_seen_at' => now()]);
            $user->currentAccessToken()?->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }
}
