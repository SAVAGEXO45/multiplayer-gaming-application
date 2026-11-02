<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\Game;
use App\Models\GameResult;
use App\Models\GameRoom;
use App\Models\PlayerStatistics;
use App\Models\RewardTransaction;
use App\Services\LeaderboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request, LeaderboardService $leaderboard): Response
    {
        $user = $request->user();
        $statistics = PlayerStatistics::query()->firstOrCreate(['user_id' => $user->id]);

        $recentGames = GameResult::query()
            ->where('user_id', $user->id)
            ->with(['game', 'session.results.user'])
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (GameResult $result): array => [
                'id' => $result->id,
                'result' => $result->result->value,
                'points_awarded' => $result->points_awarded,
                'score' => $result->score,
                'is_multiplayer' => $result->is_multiplayer,
                'created_at' => $result->created_at?->toIso8601String(),
                'game' => [
                    'name' => $result->game->name,
                    'slug' => $result->game->slug,
                    'icon' => $result->game->icon,
                    'accent' => $result->game->accent,
                ],
                'opponent' => $result->session?->results
                    ->first(fn ($other) => $other->user_id !== $user->id)?->user?->username,
            ]);

        $activeRooms = GameRoom::query()
            ->whereHas('activePlayers', fn ($query) => $query->where('user_id', $user->id))
            ->whereIn('status', ['waiting', 'in_progress'])
            ->with(['game', 'activePlayers'])
            ->get()
            ->map(fn (GameRoom $room): array => [
                'code' => $room->code,
                'name' => $room->name,
                'status' => $room->status->value,
                'game' => ['name' => $room->game->name, 'slug' => $room->game->slug],
                'players_count' => $room->activePlayers->count(),
                'max_players' => $room->max_players,
            ]);

        $achievements = Achievement::query()->where('is_active', true)->get();
        $unlockedIds = $user->achievements()->pluck('achievement_id')->all();
        $latestAchievements = $user->achievements()
            ->with('achievement')
            ->latest('unlocked_at')
            ->limit(3)
            ->get()
            ->map(fn ($unlock): array => [
                'name' => $unlock->achievement->name,
                'icon' => $unlock->achievement->icon,
                'tier' => $unlock->achievement->tier,
                'unlocked_at' => $unlock->unlocked_at?->toIso8601String(),
            ]);

        return Inertia::render('Dashboard', [
            'stats' => [
                ...$statistics->only([
                    'games_played', 'wins', 'losses', 'draws', 'points',
                    'current_streak', 'best_streak', 'multiplayer_wins',
                ]),
                'win_rate' => $statistics->winRate(),
                'rank' => $leaderboard->rankOf($user),
            ],
            'recentGames' => $recentGames,
            'topPlayers' => $leaderboard->global(5),
            'activeRooms' => $activeRooms,
            'games' => Game::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(['id', 'slug', 'name', 'tagline', 'icon', 'accent', 'supports_multiplayer', 'points_win']),
            'achievements' => [
                'unlocked' => count($unlockedIds),
                'total' => $achievements->count(),
                'latest' => $latestAchievements,
            ],
            'pointsHistory' => RewardTransaction::query()
                ->where('user_id', $user->id)
                ->latest()
                ->limit(6)
                ->get(['id', 'amount', 'reason', 'created_at']),
            'notifications' => $user->notifications()
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn ($notification): array => [
                    'id' => $notification->id,
                    'title' => $notification->data['title'] ?? 'Notification',
                    'message' => $notification->data['message'] ?? '',
                    'read_at' => $notification->read_at?->toIso8601String(),
                    'created_at' => $notification->created_at?->diffForHumans(),
                ]),
        ]);
    }
}
