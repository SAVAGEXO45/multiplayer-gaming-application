<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\FriendRequest;
use App\Models\User;
use App\Services\FriendService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class FriendApiController extends Controller
{
    public function __construct(
        private readonly FriendService $friendService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $friends = $user->friends()->with('globalStatistic')->orderByDesc('is_online')->get();
        $incoming = $user->receivedFriendRequests()->with('sender.globalStatistic')->where('status', 'pending')->latest()->get();
        $outgoing = $user->sentFriendRequests()->with('receiver.globalStatistic')->where('status', 'pending')->latest()->get();

        return response()->json([
            'success' => true,
            'data' => [
                'friends' => UserResource::collection($friends),
                'incoming_requests' => $incoming,
                'outgoing_requests' => $outgoing,
            ],
        ]);
    }

    public function sendRequest(Request $request): JsonResponse
    {
        $request->validate([
            'username' => ['required', 'string', 'exists:users,username'],
        ]);

        try {
            $receiver = User::where('username', strtolower($request->input('username')))->firstOrFail();
            $friendRequest = $this->friendService->sendRequest($request->user(), $receiver);

            return response()->json([
                'success' => true,
                'message' => $friendRequest->status === 'accepted'
                    ? "You and {$receiver->username} are now friends!"
                    : "Friend request sent to {$receiver->username}.",
                'data' => $friendRequest,
            ], 201);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function acceptRequest(Request $request, FriendRequest $friendRequest): JsonResponse
    {
        try {
            $updated = $this->friendService->acceptRequest($friendRequest, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Friend request accepted.',
                'data' => $updated,
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function rejectRequest(Request $request, FriendRequest $friendRequest): JsonResponse
    {
        try {
            $updated = $this->friendService->rejectRequest($friendRequest, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Friend request declined.',
                'data' => $updated,
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function removeFriend(Request $request, User $friend): JsonResponse
    {
        $this->friendService->removeFriend($request->user(), $friend);

        return response()->json([
            'success' => true,
            'message' => 'Friend removed.',
        ]);
    }
}
