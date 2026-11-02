<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateRoomRequest;
use App\Http\Requests\JoinRoomRequest;
use App\Http\Resources\GameRoomResource;
use App\Http\Resources\GameSessionResource;
use App\Models\Game;
use App\Models\GameRoom;
use App\Services\RoomService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoomController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $rooms = GameRoom::query()
            ->whereIn('status', ['waiting', 'in_progress'])
            ->with(['game', 'host', 'activePlayers.user'])
            ->latest()
            ->paginate(12)
            ->withQueryString()
            ->through(fn (GameRoom $room): array => [
                'code' => $room->code,
                'name' => $room->name,
                'status' => $room->status->value,
                'max_players' => $room->max_players,
                'is_private' => $room->is_private,
                'game' => [
                    'name' => $room->game->name,
                    'slug' => $room->game->slug,
                    'icon' => $room->game->icon,
                    'accent' => $room->game->accent,
                ],
                'host' => $room->host->username,
                'players_count' => $room->activePlayers->count(),
                'is_member' => $room->activePlayers->contains(fn ($player) => $player->user_id === $user->id),
            ]);

        $myRooms = GameRoom::query()
            ->whereHas('activePlayers', fn ($query) => $query->where('user_id', $user->id))
            ->whereIn('status', ['waiting', 'in_progress'])
            ->with(['game', 'host', 'activePlayers.user'])
            ->get()
            ->map(fn (GameRoom $room): array => [
                'code' => $room->code,
                'name' => $room->name,
                'status' => $room->status->value,
                'game' => ['name' => $room->game->name, 'slug' => $room->game->slug, 'icon' => $room->game->icon],
                'players_count' => $room->activePlayers->count(),
                'max_players' => $room->max_players,
            ]);

        return Inertia::render('rooms/Index', [
            'rooms' => $rooms,
            'myRooms' => $myRooms,
            'games' => Game::query()
                ->where('is_active', true)
                ->where('supports_multiplayer', true)
                ->orderBy('sort_order')
                ->get(['id', 'slug', 'name', 'icon', 'accent', 'min_players', 'max_players']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('rooms/Create', [
            'games' => Game::query()
                ->where('is_active', true)
                ->where('supports_multiplayer', true)
                ->orderBy('sort_order')
                ->get(['id', 'slug', 'name', 'tagline', 'icon', 'accent', 'min_players', 'max_players']),
        ]);
    }

    public function store(CreateRoomRequest $request, RoomService $rooms): RedirectResponse
    {
        $game = Game::query()->findOrFail($request->validated('game_id'));

        $room = $rooms->create($request->user(), $game, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Room created. Share the code with a friend.']);

        return to_route('rooms.show', $room->code);
    }

    public function show(Request $request, GameRoom $room): Response
    {
        $this->authorize('view', $room);

        $user = $request->user();
        $room->load(['game', 'host', 'activePlayers.user', 'currentSession.game', 'currentSession.results.user']);
        $isMember = $room->activePlayers->contains(fn ($player) => $player->user_id === $user->id);
        $session = $room->currentSession;

        return Inertia::render('rooms/Show', [
            'room' => [
                'code' => $room->code,
                'name' => $room->name,
                'status' => $room->status->value,
                'max_players' => $room->max_players,
                'is_private' => $room->is_private,
                'has_password' => $room->hasPassword(),
                'game' => [
                    'slug' => $room->game->slug,
                    'name' => $room->game->name,
                    'icon' => $room->game->icon,
                    'accent' => $room->game->accent,
                    'instructions' => $room->game->instructions,
                ],
                'host' => [
                    'username' => $room->host->username,
                    'avatar_url' => $room->host->avatarUrl(),
                ],
                'players' => $room->activePlayers->map(fn ($player): array => [
                    'user_id' => $player->user_id,
                    'username' => $player->user->username,
                    'name' => $player->user->name,
                    'avatar_url' => $player->user->avatarUrl(),
                    'is_ready' => $player->is_ready,
                    'is_host' => $player->is_host,
                ]),
                'players_count' => $room->activePlayers->count(),
            ],
            'session' => $isMember && $session !== null ? new GameSessionResource($session) : null,
            'canJoin' => ! $isMember && $room->status->value === 'waiting' && $room->activePlayers->count() < $room->max_players,
            'canStart' => $isMember && $room->host_id === $user->id,
            'isMember' => $isMember,
            'isHost' => $room->host_id === $user->id,
            'requiresPassword' => $room->hasPassword() && ! $isMember,
        ]);
    }

    public function join(JoinRoomRequest $request, GameRoom $room, RoomService $rooms): RedirectResponse
    {
        $rooms->join($request->user(), $room, $request->validated('password'));

        Inertia::flash('toast', ['type' => 'success', 'message' => "You joined {$room->name}."]);

        return to_route('rooms.show', $room->code);
    }

    public function leave(Request $request, GameRoom $room, RoomService $rooms): RedirectResponse
    {
        $this->authorize('leave', $room);

        $rooms->leave($request->user(), $room);

        Inertia::flash('toast', ['type' => 'info', 'message' => 'You left the room.']);

        return to_route('rooms.index');
    }

    public function ready(Request $request, GameRoom $room, RoomService $rooms): RedirectResponse
    {
        $rooms->toggleReady($request->user(), $room);

        return back();
    }

    public function start(Request $request, GameRoom $room, RoomService $rooms): RedirectResponse
    {
        $this->authorize('start', $room);

        $rooms->start($request->user(), $room);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Match started. Good luck!']);

        return to_route('rooms.show', $room->code);
    }

    public function state(Request $request, GameRoom $room): JsonResponse
    {
        $this->authorize('view', $room);

        $room->load(['game', 'host', 'activePlayers.user', 'currentSession.game', 'currentSession.results.user']);
        $isMember = $room->activePlayers->contains(fn ($player) => $player->user_id === $request->user()->id);

        return response()->json([
            'success' => true,
            'data' => [
                'room' => new GameRoomResource($room),
                'session' => $isMember && $room->currentSession !== null
                    ? new GameSessionResource($room->currentSession)
                    : null,
            ],
        ]);
    }
}
