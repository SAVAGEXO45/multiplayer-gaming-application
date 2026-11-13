<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\GameRoom;
use App\Models\GameSession;
use App\Models\Leaderboard;
use App\Models\PlayerStatistic;
use App\Models\RoomPlayer;
use App\Models\UserGameActivity;
use App\Services\ScoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GameWebController extends Controller
{
    public function index(Request $request): View
    {
        $category = $request->query('category');
        $search = trim((string) $request->query('q', ''));
        $sort = $request->query('sort', 'featured');

        $allCategories = Game::where('is_active', true)
            ->select('category')
            ->distinct()
            ->pluck('category')
            ->all();

        $query = Game::query()
            ->where('is_active', true)
            ->when($category, fn ($q) => $q->where('category', $category))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->withCount([
                'activeRooms',
                'sessions as completed_sessions_count' => fn ($q) => $q->where('status', 'completed'),
            ]);

        match ($sort) {
            'trending' => $query->orderByDesc('is_trending')->orderByDesc('plays_count'),
            'new' => $query->orderByDesc('is_new_release')->orderByDesc('id'),
            'rating' => $query->orderByDesc('rating')->orderByDesc('plays_count'),
            'popular' => $query->orderByDesc('plays_count'),
            'name' => $query->orderBy('name'),
            default => $query->orderByDesc('is_featured')->orderBy('id'),
        };

        $games = $query->get();

        $activePlayersByGame = RoomPlayer::query()
            ->selectRaw('game_rooms.game_id, count(distinct room_players.user_id) as players_count')
            ->join('game_rooms', 'game_rooms.id', '=', 'room_players.game_room_id')
            ->whereIn('game_rooms.status', ['waiting', 'in_progress'])
            ->whereIn('room_players.status', ['active', 'ready'])
            ->groupBy('game_rooms.game_id')
            ->pluck('players_count', 'game_id');

        $favoriteGameIds = [];
        $userActivitiesByGame = collect();
        if ($request->user()) {
            $userActivitiesByGame = UserGameActivity::where('user_id', $request->user()->id)
                ->get()
                ->keyBy('game_id');
            $favoriteGameIds = $userActivitiesByGame->where('is_favorite', true)->keys()->all();
        }

        $games->each(function (Game $game) use ($activePlayersByGame, $favoriteGameIds, $userActivitiesByGame) {
            $game->active_players_count = (int) ($activePlayersByGame[$game->id] ?? 0);
            $game->is_favorited_by_user = in_array($game->id, $favoriteGameIds, true);
            $game->user_high_score = (int) ($userActivitiesByGame[$game->id]->high_score ?? 0);
        });

        $publicRooms = GameRoom::with(['game', 'host', 'roomPlayers.user'])
            ->where('visibility', 'public')
            ->whereIn('status', ['waiting', 'in_progress'])
            ->latest()
            ->limit(12)
            ->get();

        return view('games.index', compact(
            'games',
            'publicRooms',
            'category',
            'search',
            'sort',
            'allCategories'
        ));
    }

    public function show(Request $request, Game $game): View
    {
        $game->loadCount([
            'activeRooms',
            'sessions as completed_sessions_count' => fn ($q) => $q->where('status', 'completed'),
        ]);

        $game->active_players_count = RoomPlayer::query()
            ->join('game_rooms', 'game_rooms.id', '=', 'room_players.game_room_id')
            ->where('game_rooms.game_id', $game->id)
            ->whereIn('game_rooms.status', ['waiting', 'in_progress'])
            ->whereIn('room_players.status', ['active', 'ready'])
            ->distinct('room_players.user_id')
            ->count('room_players.user_id');

        $publicRooms = $game->rooms()
            ->with(['host', 'roomPlayers.user'])
            ->where('visibility', 'public')
            ->whereIn('status', ['waiting', 'in_progress'])
            ->latest()
            ->limit(10)
            ->get();

        $topPlayers = Leaderboard::with('user')
            ->where('game_id', $game->id)
            ->where('period_type', 'global')
            ->where('period_key', 'all')
            ->orderByDesc('points')
            ->orderByDesc('wins')
            ->limit(10)
            ->get();

        $recentMatches = GameSession::with(['room.roomPlayers.user', 'winner'])
            ->where('game_id', $game->id)
            ->where('status', 'completed')
            ->latest('ended_at')
            ->limit(8)
            ->get();

        $myGameStat = null;
        $myActivity = null;
        if ($request->user()) {
            $myGameStat = PlayerStatistic::where('user_id', $request->user()->id)
                ->where('game_id', $game->id)
                ->first();

            $myActivity = UserGameActivity::firstOrCreate(
                ['user_id' => $request->user()->id, 'game_id' => $game->id],
                [
                    'is_favorite' => false,
                    'plays_count' => 0,
                    'high_score' => 0,
                    'max_level' => 1,
                    'last_played_at' => now(),
                ]
            );

            $myActivity->update(['last_played_at' => now()]);
        }

        $relatedGames = Game::where('is_active', true)
            ->where('id', '!=', $game->id)
            ->orderByRaw('CASE WHEN category = ? THEN 0 ELSE 1 END', [$game->category])
            ->orderByDesc('is_featured')
            ->limit(4)
            ->get();

        return view('games.show', compact(
            'game',
            'publicRooms',
            'topPlayers',
            'recentMatches',
            'myGameStat',
            'myActivity',
            'relatedGames'
        ));
    }

    public function submitScore(Request $request, Game $game, ScoringService $scoringService): JsonResponse
    {
        $validated = $request->validate([
            'score' => ['required', 'integer', 'min:0', 'max:2000000'],
            'level' => ['nullable', 'integer', 'min:1', 'max:99'],
            'victory' => ['nullable', 'boolean'],
        ]);

        $result = $scoringService->recordSoloRunScore(
            $request->user(),
            $game,
            (int) $validated['score'],
            (int) ($validated['level'] ?? 1),
            (bool) ($validated['victory'] ?? true)
        );

        return response()->json([
            'status' => 'ok',
            'message' => $result['is_new_high_score']
                ? "New Personal Record: " . number_format($result['high_score']) . " pts!"
                : "Score recorded! +" . $result['points_earned'] . " Arena XP",
            'data' => $result,
        ]);
    }

    public function toggleFavorite(Request $request, Game $game): JsonResponse|RedirectResponse
    {
        $activity = UserGameActivity::firstOrCreate(
            ['user_id' => $request->user()->id, 'game_id' => $game->id],
            [
                'is_favorite' => false,
                'plays_count' => 0,
                'high_score' => 0,
                'max_level' => 1,
            ]
        );

        $activity->update([
            'is_favorite' => !$activity->is_favorite,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'ok',
                'is_favorite' => $activity->is_favorite,
                'message' => $activity->is_favorite
                    ? "{$game->name} added to your Favorites!"
                    : "{$game->name} removed from your Favorites.",
            ]);
        }

        return back()->with(
            'success',
            $activity->is_favorite
                ? "{$game->name} added to your Favorite Games!"
                : "{$game->name} removed from Favorites."
        );
    }

    public function library(Request $request): View
    {
        $user = $request->user();

        $activities = UserGameActivity::with('game')
            ->where('user_id', $user->id)
            ->get();

        $favoriteGames = $activities
            ->where('is_favorite', true)
            ->filter(fn ($a) => $a->game && $a->game->is_active)
            ->values();

        $recentlyPlayed = $activities
            ->whereNotNull('last_played_at')
            ->sortByDesc('last_played_at')
            ->filter(fn ($a) => $a->game && $a->game->is_active)
            ->values();

        $allGames = Game::where('is_active', true)->orderBy('id')->get();

        return view('library.index', compact(
            'user',
            'activities',
            'favoriteGames',
            'recentlyPlayed',
            'allGames'
        ));
    }
}
