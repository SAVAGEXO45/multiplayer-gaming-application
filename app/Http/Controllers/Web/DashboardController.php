<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\GameRoom;
use App\Models\GameSession;
use App\Models\PlayerStatistic;
use App\Models\UserGameActivity;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $globalStat = PlayerStatistic::firstOrCreate(
            ['user_id' => $user->id, 'game_id' => null],
            [
                'games_played' => 0,
                'games_won' => 0,
                'games_lost' => 0,
                'games_drawn' => 0,
                'win_percentage' => 0,
                'total_points' => 0,
                'current_streak' => 0,
                'best_streak' => 0,
            ]
        );

        // Calculate dynamic rank position if not yet assigned
        if (!$globalStat->rank_position) {
            $higherCount = PlayerStatistic::whereNull('game_id')
                ->where('total_points', '>', $globalStat->total_points)
                ->count();
            $globalStat->rank_position = $higherCount + 1;
        }

        $gameStats = PlayerStatistic::with('game')
            ->where('user_id', $user->id)
            ->whereNotNull('game_id')
            ->get()
            ->keyBy('game_id');

        $games = Game::where('is_active', true)->withCount('activeRooms')->get();

        // Recent game sessions this player participated in
        $userRoomIds = $user->roomMemberships()->pluck('game_room_id');
        $recentSessions = GameSession::with(['game', 'room.roomPlayers.user', 'winner'])
            ->whereIn('game_room_id', $userRoomIds)
            ->where('status', 'completed')
            ->latest('ended_at')
            ->limit(8)
            ->get();

        // Active rooms player is in or public rooms waiting for players
        $myActiveRooms = GameRoom::with(['game', 'host', 'roomPlayers.user'])
            ->whereIn('id', $userRoomIds)
            ->whereIn('status', ['waiting', 'in_progress'])
            ->latest()
            ->limit(5)
            ->get();

        $openLobbyRooms = GameRoom::with(['game', 'host', 'roomPlayers.user'])
            ->where('visibility', 'public')
            ->whereIn('status', ['waiting', 'in_progress'])
            ->latest()
            ->limit(6)
            ->get();

        $onlineFriends = $user->friends()
            ->with('globalStatistic')
            ->orderByDesc('is_online')
            ->limit(8)
            ->get();

        $unlockedAchievements = $user->achievements()->limit(6)->get();
        $totalAchievementsCount = Achievement::count();
        $recentActivities = $user->platformNotifications()->limit(6)->get();

        $userGameActivities = UserGameActivity::with('game')
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('game_id');

        $favoriteGames = $userGameActivities->where('is_favorite', true)->values();
        $recentlyPlayedGames = $userGameActivities->whereNotNull('last_played_at')->sortByDesc('last_played_at')->take(4)->values();

        return view('dashboard', compact(
            'user',
            'globalStat',
            'gameStats',
            'games',
            'recentSessions',
            'myActiveRooms',
            'openLobbyRooms',
            'onlineFriends',
            'unlockedAchievements',
            'totalAchievementsCount',
            'recentActivities',
            'userGameActivities',
            'favoriteGames',
            'recentlyPlayedGames'
        ));
    }
}
