<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlatformNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->platformNotifications()
            ->limit(40)
            ->get();

        return response()->json([
            'success' => true,
            'unread_count' => $request->user()->unreadPlatformNotifications()->count(),
            'data' => $notifications,
        ]);
    }

    public function markRead(Request $request, PlatformNotification $notification): JsonResponse
    {
        if ((int) $notification->user_id !== (int) $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden.',
            ], 403);
        }

        $notification->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read.',
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadPlatformNotifications()->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read.',
        ]);
    }
}
