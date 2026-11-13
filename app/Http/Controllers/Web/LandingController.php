<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\GameRoom;
use App\Models\GameSession;
use App\Models\Leaderboard;
use App\Models\RoomPlayer;
use App\Models\User;
use App\Models\UserGameActivity;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function index(Request $request): View
    {
        $games = Game::where('is_active', true)
            ->withCount([
                'activeRooms',
                'sessions as completed_sessions_count' => fn ($q) => $q->where('status', 'completed'),
            ])
            ->orderBy('id')
            ->get();

        // Calculate active players per game from active room participants
        $activePlayersByGame = RoomPlayer::query()
            ->selectRaw('game_rooms.game_id, count(distinct room_players.user_id) as players_count')
            ->join('game_rooms', 'game_rooms.id', '=', 'room_players.game_room_id')
            ->whereIn('game_rooms.status', ['waiting', 'in_progress'])
            ->whereIn('room_players.status', ['active', 'ready'])
            ->groupBy('game_rooms.game_id')
            ->pluck('players_count', 'game_id');

        $favoriteGameIds = [];
        $recentlyPlayedGames = collect();
        $favoriteGames = collect();

        if ($request->user()) {
            $activities = UserGameActivity::with('game')
                ->where('user_id', $request->user()->id)
                ->get();

            $favoriteGameIds = $activities->where('is_favorite', true)->pluck('game_id')->all();
            $favoriteGames = $games->whereIn('id', $favoriteGameIds)->values();

            $recentIds = $activities
                ->whereNotNull('last_played_at')
                ->sortByDesc('last_played_at')
                ->pluck('game_id')
                ->take(6)
                ->all();

            $recentlyPlayedGames = collect($recentIds)
                ->map(fn ($id) => $games->firstWhere('id', $id))
                ->filter()
                ->values();
        }

        $games->each(function (Game $game) use ($activePlayersByGame, $favoriteGameIds) {
            $game->active_players_count = (int) ($activePlayersByGame[$game->id] ?? 0);
            $game->is_favorited_by_user = in_array($game->id, $favoriteGameIds, true);
        });

        $featuredGames = $games->where('is_featured', true)->values();
        if ($featuredGames->isEmpty()) {
            $featuredGames = $games->take(4)->values();
        }

        $trendingGames = $games->where('is_trending', true)->sortByDesc('plays_count')->values();
        if ($trendingGames->isEmpty()) {
            $trendingGames = $games->sortByDesc('plays_count')->take(6)->values();
        }

        $newReleases = $games->where('is_new_release', true)->values();
        if ($newReleases->isEmpty()) {
            $newReleases = $games->take(4)->values();
        }

        $categories = $games->groupBy('category')->map(fn ($group, $cat) => [
            'name' => $cat,
            'count' => $group->count(),
            'sample_game' => $group->first(),
        ])->values();

        $activeRooms = GameRoom::with(['game', 'host', 'roomPlayers.user'])
            ->where('visibility', 'public')
            ->whereIn('status', ['waiting', 'in_progress'])
            ->latest()
            ->limit(6)
            ->get();

        $topPlayers = Leaderboard::with('user')
            ->where('period_type', 'global')
            ->where('period_key', 'all')
            ->whereNull('game_id')
            ->orderByDesc('points')
            ->orderByDesc('wins')
            ->limit(8)
            ->get();

        $stats = [
            'players_count' => User::count(),
            'active_rooms' => GameRoom::whereIn('status', ['waiting', 'in_progress'])->count(),
            'games_available' => $games->count(),
            'matches_played' => GameSession::where('status', 'completed')->count() + (int) $games->sum('plays_count'),
            'online_players' => User::where('is_online', true)->count(),
        ];

        return view('welcome', compact(
            'games',
            'featuredGames',
            'trendingGames',
            'newReleases',
            'categories',
            'favoriteGames',
            'recentlyPlayedGames',
            'activeRooms',
            'topPlayers',
            'stats'
        ));
    }

    public function tournaments(): View
    {
        $games = Game::where('is_active', true)->withCount('activeRooms')->get();

        $topPlayers = Leaderboard::with('user')
            ->where('period_type', 'global')
            ->where('period_key', 'all')
            ->whereNull('game_id')
            ->orderByDesc('points')
            ->orderByDesc('wins')
            ->limit(10)
            ->get();

        $activeRooms = GameRoom::with(['game', 'host', 'roomPlayers.user'])
            ->where('visibility', 'public')
            ->whereIn('status', ['waiting', 'in_progress'])
            ->latest()
            ->limit(8)
            ->get();

        return view('tournaments.index', compact('games', 'topPlayers', 'activeRooms'));
    }
}
