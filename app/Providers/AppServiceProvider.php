<?php

namespace App\Providers;

use App\Models\GameRoom;
use App\Models\GameSession;
use App\Policies\GameRoomPolicy;
use App\Policies\GameSessionPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(GameSession::class, GameSessionPolicy::class);
        Gate::policy(GameRoom::class, GameRoomPolicy::class);

        Gate::define('admin', fn ($user): bool => $user->isAdmin());

        RateLimiter::for('api', fn (Request $request): Limit => Limit::perMinute(60)
            ->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('game-moves', fn (Request $request): Limit => Limit::perMinute(45)
            ->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('auth', fn (Request $request): Limit => Limit::perMinute(6)
            ->by($request->input('email').'|'.$request->ip()));
    }
}
