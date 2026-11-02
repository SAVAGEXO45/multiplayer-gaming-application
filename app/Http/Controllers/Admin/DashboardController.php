<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\GameResult;
use App\Models\GameRoom;
use App\Models\GameSession;
use App\Models\PlayerStatistics;
use App\Models\RewardTransaction;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Services\LeaderboardService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(LeaderboardService $leaderboard): Response
    {
        $registrations = User::query()
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->get(['created_at'])
            ->groupBy(fn (User $user): string => $user->created_at->format('Y-m-d'))
            ->map(fn ($group): int => $group->count());

        $activity = GameResult::query()
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->get(['created_at'])
            ->groupBy(fn (GameResult $result): string => $result->created_at->format('Y-m-d'))
            ->map(fn ($group): int => $group->count());

        $days = collect(range(6, 0))->map(fn (int $offset): array => [
            'date' => now()->subDays($offset)->format('Y-m-d'),
            'label' => now()->subDays($offset)->format('D'),
            'registrations' => $registrations[now()->subDays($offset)->format('Y-m-d')] ?? 0,
            'matches' => $activity[now()->subDays($offset)->format('Y-m-d')] ?? 0,
        ]);

        return Inertia::render('admin/Dashboard', [
            'counts' => [
                'users' => User::query()->count(),
                'players' => User::query()->where('role', 'player')->count(),
                'suspended' => User::query()->where('is_suspended', true)->count(),
                'games_played' => GameResult::query()->count(),
                'active_rooms' => GameRoom::query()->where('status', 'in_progress')->count(),
                'waiting_rooms' => GameRoom::query()->where('status', 'waiting')->count(),
                'sessions_today' => GameSession::query()->whereDate('created_at', today())->count(),
                'points_awarded' => (int) RewardTransaction::query()->where('amount', '>', 0)->sum('amount'),
                'total_points' => (int) PlayerStatistics::query()->sum('points'),
                'games' => Game::query()->count(),
            ],
            'recentResults' => GameResult::query()
                ->with(['user', 'game'])
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn (GameResult $result): array => [
                    'id' => $result->id,
                    'result' => $result->result->value,
                    'points_awarded' => $result->points_awarded,
                    'is_multiplayer' => $result->is_multiplayer,
                    'created_at' => $result->created_at?->diffForHumans(),
                    'user' => $result->user->username,
                    'game' => $result->game->name,
                ]),
            'suspicious' => SecurityEvent::query()
                ->with('user')
                ->latest()
                ->limit(8)
                ->get()
                ->map(fn (SecurityEvent $event): array => [
                    'id' => $event->id,
                    'type' => $event->type,
                    'severity' => $event->severity,
                    'description' => $event->description,
                    'user' => $event->user?->username,
                    'created_at' => $event->created_at?->diffForHumans(),
                ]),
            'topPlayers' => $leaderboard->global(5),
            'activity' => $days,
            'activeRooms' => GameRoom::query()
                ->whereIn('status', ['waiting', 'in_progress'])
                ->with(['game', 'host', 'activePlayers'])
                ->latest()
                ->limit(6)
                ->get()
                ->map(fn (GameRoom $room): array => [
                    'code' => $room->code,
                    'name' => $room->name,
                    'status' => $room->status->value,
                    'game' => $room->game->name,
                    'host' => $room->host->username,
                    'players_count' => $room->activePlayers->count(),
                    'max_players' => $room->max_players,
                ]),
        ]);
    }
}
