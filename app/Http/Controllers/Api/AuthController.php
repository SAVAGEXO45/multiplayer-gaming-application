<?php

namespace App\Http\Controllers\Api;

use App\Actions\Fortify\CreateNewUser;
use App\Http\Controllers\Api\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use ApiResponse;

    public function register(Request $request, CreateNewUser $creator): JsonResponse
    {
        $user = $creator->create($request->only([
            'name', 'username', 'email', 'password', 'password_confirmation',
        ]));

        $token = $user->createToken('api')->plainTextToken;

        // Treat the freshly registered account as the viewer so the resource
        // returns their own email address in the creation response.
        $request->setUserResolver(fn () => $user);

        return $this->success([
            'user' => new UserResource($user->load('statistics')),
            'token' => $token,
        ], 'Account created.', 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        /** @var User|null $user */
        $user = User::query()->where('email', $credentials['email'])->first();

        if ($user === null || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->isSuspended()) {
            return $this->error('This account has been suspended.', 403);
        }

        $token = $user->createToken('api')->plainTextToken;

        return $this->success([
            'user' => new UserResource($user->load('statistics')),
            'token' => $token,
        ], 'Signed in.');
    }

    public function user(Request $request): JsonResponse
    {
        return $this->success(new UserResource($request->user()->load('statistics')));
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return $this->success(null, 'Signed out.');
    }
}
