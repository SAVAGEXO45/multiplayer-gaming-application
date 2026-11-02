<?php

namespace App\Http\Controllers;

use App\Http\Requests\StartGameRequest;
use App\Models\Game;
use App\Models\GameResult;
use App\Models\PlayerStatistics;
use App\Services\GameService;
use App\Services\LeaderboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GameController extends Controller
{
    public function index(Request $request, LeaderboardService $leaderboard): Response
    {
        $user = $request->user();
        $perGame = $leaderboard->perGameStats($user);
        $statistics = PlayerStatistics::query()->firstOrCreate(['user_id' => $user->id]);

        return Inertia::render('games/Index', [
            'games' => Game::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->map(fn (Game $game): array => [
                    'id' => $game->id,
                    'slug' => $game->slug,
                    'name' => $game->name,
                    'tagline' => $game->tagline,
                    'description' => $game->description,
                    'icon' => $game->icon,
                    'accent' => $game->accent,
                    'min_players' => $game->min_players,
                    'max_players' => $game->max_players,
                    'supports_multiplayer' => $game->supports_multiplayer,
                    'points_win' => $game->points_win,
                    'difficulties' => $game->difficultyOptions(),
                    'stats' => $perGame[$game->slug] ?? [
                        'games_played' => 0,
                        'wins' => 0,
                        'losses' => 0,
                        'draws' => 0,
                        'points' => 0,
                        'win_rate' => 0,
                    ],
                ]),
            'summary' => [
                'games_played' => $statistics->games_played,
                'wins' => $statistics->wins,
                'points' => $statistics->points,
                'win_rate' => $statistics->winRate(),
            ],
        ]);
    }

    public function show(Request $request, Game $game, LeaderboardService $leaderboard): Response
    {
        abort_unless($game->is_active, 404);

        $user = $request->user();
        $perGame = $leaderboard->perGameStats($user);

        $history = GameResult::query()
            ->where('user_id', $user->id)
            ->where('game_id', $game->id)
            ->with(['session.results.user'])
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (GameResult $result): array => [
                'id' => $result->id,
                'result' => $result->result->value,
                'points_awarded' => $result->points_awarded,
                'score' => $result->score,
                'is_multiplayer' => $result->is_multiplayer,
                'created_at' => $result->created_at?->toIso8601String(),
                'opponent' => $result->session?->results
                    ->first(fn ($other) => $other->user_id !== $user->id)?->user?->username,
            ]);

        return Inertia::render('games/Show', [
            'game' => [
                'id' => $game->id,
                'slug' => $game->slug,
                'name' => $game->name,
                'tagline' => $game->tagline,
                'description' => $game->description,
                'instructions' => $game->instructions,
                'rules' => $game->rules,
                'icon' => $game->icon,
                'accent' => $game->accent,
                'min_players' => $game->min_players,
                'max_players' => $game->max_players,
                'supports_multiplayer' => $game->supports_multiplayer,
                'points_win' => $game->points_win,
                'points_draw' => $game->points_draw,
                'points_loss' => $game->points_loss,
                'points_participation' => $game->points_participation,
                'difficulties' => $game->difficultyOptions(),
            ],
            'stats' => $perGame[$game->slug] ?? [
                'games_played' => 0,
                'wins' => 0,
                'losses' => 0,
                'draws' => 0,
                'points' => 0,
                'win_rate' => 0,
            ],
            'history' => $history,
            'topPlayers' => $leaderboard->forPeriod('all', $game, 5),
            'openRooms' => $game->rooms()
                ->where('status', 'waiting')
                ->with(['host', 'activePlayers'])
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn ($room): array => [
                    'code' => $room->code,
                    'name' => $room->name,
                    'is_private' => $room->is_private,
                    'players_count' => $room->activePlayers->count(),
                    'max_players' => $room->max_players,
                    'host' => $room->host->username,
                ]),
        ]);
    }

    public function start(StartGameRequest $request, Game $game, GameService $games): RedirectResponse
    {
        $session = $games->startSolo($request->user(), $game, $request->validated('difficulty'));

        return to_route('games.sessions.show', $session->uuid);
    }
}
