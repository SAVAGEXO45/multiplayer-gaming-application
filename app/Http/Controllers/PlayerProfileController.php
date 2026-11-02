<?php

namespace App\Http\Controllers;

use App\Models\GameResult;
use App\Models\PlayerStatistics;
use App\Models\User;
use App\Services\AchievementService;
use App\Services\LeaderboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlayerProfileController extends Controller
{
    public function show(Request $request, User $user, LeaderboardService $leaderboard, AchievementService $achievements): Response
    {
        abort_if($user->is_suspended, 404);

        $statistics = PlayerStatistics::query()->firstOrCreate(['user_id' => $user->id]);

        $unlocked = $user->achievements()
            ->with('achievement')
            ->latest('unlocked_at')
            ->get()
            ->map(fn ($unlock): array => [
                'slug' => $unlock->achievement->slug,
                'name' => $unlock->achievement->name,
                'description' => $unlock->achievement->description,
                'icon' => $unlock->achievement->icon,
                'tier' => $unlock->achievement->tier,
                'points_reward' => $unlock->achievement->points_reward,
                'unlocked_at' => $unlock->unlocked_at?->toIso8601String(),
            ]);

        $recentGames = GameResult::query()
            ->where('user_id', $user->id)
            ->with(['game', 'session.results.user'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (GameResult $result): array => [
                'id' => $result->id,
                'result' => $result->result->value,
                'points_awarded' => $result->points_awarded,
                'is_multiplayer' => $result->is_multiplayer,
                'created_at' => $result->created_at?->toIso8601String(),
                'game' => [
                    'name' => $result->game->name,
                    'slug' => $result->game->slug,
                    'icon' => $result->game->icon,
                ],
                'opponent' => $result->session?->results
                    ->first(fn ($other) => $other->user_id !== $user->id)?->user?->username,
            ]);

        return Inertia::render('players/Show', [
            'player' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'avatar_url' => $user->avatarUrl(),
                'bio' => $user->bio,
                'role' => $user->role->value,
                'created_at' => $user->created_at?->toIso8601String(),
            ],
            'stats' => [
                ...$statistics->only([
                    'games_played', 'wins', 'losses', 'draws', 'points',
                    'current_streak', 'best_streak', 'multiplayer_wins',
                ]),
                'win_rate' => $statistics->winRate(),
            ],
            'perGame' => array_values($leaderboard->perGameStats($user)),
            'achievements' => $unlocked,
            'achievementProgress' => [
                'unlocked' => $unlocked->count(),
                'total' => \App\Models\Achievement::query()->where('is_active', true)->count(),
            ],
            'recentGames' => $recentGames,
            'rank' => $leaderboard->rankOf($user),
            'isSelf' => $request->user()?->id === $user->id,
        ]);
    }
}
