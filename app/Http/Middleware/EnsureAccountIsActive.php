<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            if ($user->is_suspended) {
                if ($request->expectsJson() || $request->is('api/*')) {
                    $user->currentAccessToken()?->delete();

                    return response()->json([
                        'success' => false,
                        'message' => 'Your account has been suspended by an administrator.',
                    ], 403);
                }

                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors([
                    'email' => 'Your account has been suspended by an administrator.',
                ]);
            }

            // Update online heartbeat
            if (!$user->is_online || !$user->last_seen_at || $user->last_seen_at->diffInSeconds(now()) >= 60) {
                $user->forceFill([
                    'is_online' => true,
                    'last_seen_at' => now(),
                ])->saveQuietly();
            }
        }

        return $next($request);
    }
}
