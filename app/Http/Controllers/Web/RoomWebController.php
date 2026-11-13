<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Room\CreateRoomRequest;
use App\Http\Requests\Room\MakeMoveRequest;
use App\Models\Game;
use App\Models\GameRoom;
use App\Models\User;
use App\Services\RoomService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use InvalidArgumentException;

class RoomWebController extends Controller
{
    public function __construct(
        private readonly RoomService $roomService
    ) {}

    public function index(Request $request): View
    {
        $gameSlug = $request->query('game');
        $statusFilter = $request->query('status');

        $games = Game::where('is_active', true)->get();

        $rooms = GameRoom::with(['game', 'host', 'roomPlayers.user'])
            ->where('visibility', 'public')
            ->when($statusFilter, fn ($q) => $q->where('status', $statusFilter), fn ($q) => $q->whereIn('status', ['waiting', 'in_progress']))
            ->when($gameSlug, fn ($q) => $q->whereHas('game', fn ($sub) => $sub->where('slug', $gameSlug)))
            ->latest()
            ->paginate(18)
            ->withQueryString();

        return view('rooms.index', compact('rooms', 'games', 'gameSlug', 'statusFilter'));
    }

    public function store(CreateRoomRequest $request): RedirectResponse
    {
        try {
            $game = Game::where('slug', $request->validated('game_slug'))->firstOrFail();
            $room = $this->roomService->createRoom($request->user(), $game, $request->validated());

            return redirect()->route('rooms.show', $room->room_code)
                ->with('success', "Room #{$room->room_code} created! Share the room code or wait for a challenger.");
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['room' => $e->getMessage()]);
        }
    }

    public function joinByCode(Request $request): RedirectResponse
    {
        $request->validate([
            'room_code' => ['required', 'string', 'max:12'],
        ]);

        $code = strtoupper(trim($request->input('room_code')));
        $room = GameRoom::where('room_code', $code)->first();

        if (!$room) {
            return back()->withErrors(['room_code' => "No room found with code #{$code}."]);
        }

        try {
            $this->roomService->joinRoom($room, $request->user());

            return redirect()->route('rooms.show', $room->room_code)
                ->with('success', "Joined room #{$room->room_code}!");
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['room_code' => $e->getMessage()]);
        }
    }

    public function show(Request $request, GameRoom $room): View|RedirectResponse
    {
        $user = $request->user();

        // Auto-join if room is waiting and has a free slot and user is not yet in it
        if (!$room->hasPlayer($user->id) && $room->status === 'waiting' && !$room->isFull()) {
            try {
                $this->roomService->joinRoom($room, $user);
            } catch (InvalidArgumentException) {
                // Continue as spectator if public
            }
        }

        $room->refresh();
        if (!Gate::forUser($user)->allows('view', $room)) {
            abort(403, 'This is a private game room. Ask the host for the room code to join.');
        }

        $room->load([
            'game',
            'host',
            'roomPlayers.user.globalStatistic',
            'activeSession.moves.user',
            'sessions' => fn ($q) => $q->with('winner')->where('status', 'completed')->limit(10),
        ]);

        $friends = $user->friends()->orderByDesc('is_online')->get();

        return view('rooms.show', compact('room', 'friends'));
    }

    public function join(Request $request, GameRoom $room): RedirectResponse
    {
        try {
            $this->roomService->joinRoom($room, $request->user());

            return redirect()->route('rooms.show', $room->room_code)
                ->with('success', 'You have joined the match!');
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['room' => $e->getMessage()]);
        }
    }

    public function leave(Request $request, GameRoom $room): RedirectResponse
    {
        $this->roomService->leaveRoom($room, $request->user());

        return redirect()->route('games.show', $room->game->slug)
            ->with('success', "You have left room #{$room->room_code}.");
    }

    public function startOrRestart(Request $request, GameRoom $room): JsonResponse|RedirectResponse
    {
        try {
            $this->roomService->startNewSession($room, $request->user());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'New game session started!',
                    'state' => $this->buildRoomStatePayload($room->fresh(['game', 'host', 'roomPlayers.user', 'activeSession.moves.user']), $request->user()),
                ]);
            }

            return back()->with('success', 'New game session started!');
        } catch (InvalidArgumentException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->withErrors(['room' => $e->getMessage()]);
        }
    }

    public function makeMove(MakeMoveRequest $request, GameRoom $room): JsonResponse|RedirectResponse
    {
        if (!$room->hasPlayer($request->user()->id)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden: You are not a member of this game room.',
                ], 403);
            }
            abort(403, 'You are not a member of this game room.');
        }

        try {
            $this->roomService->submitMove($room, $request->user(), $request->validated());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Move submitted.',
                    'state' => $this->buildRoomStatePayload($room->fresh(['game', 'host', 'roomPlayers.user', 'activeSession.moves.user']), $request->user()),
                ]);
            }

            return back();
        } catch (InvalidArgumentException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->withErrors(['move' => $e->getMessage()]);
        }
    }

    public function state(Request $request, GameRoom $room): JsonResponse
    {
        if (!Gate::forUser($request->user())->allows('view', $room)) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $room->load(['game', 'host', 'roomPlayers.user', 'activeSession.moves.user']);

        return response()->json([
            'success' => true,
            'state' => $this->buildRoomStatePayload($room, $request->user()),
        ]);
    }

    public function invite(Request $request, GameRoom $room): RedirectResponse|JsonResponse
    {
        $request->validate([
            'username' => ['required', 'string', 'exists:users,username'],
        ]);

        try {
            $target = User::where('username', strtolower($request->input('username')))->firstOrFail();
            $this->roomService->invitePlayer($room, $request->user(), $target);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Invited {$target->username} to room #{$room->room_code}!",
                ]);
            }

            return back()->with('success', "Invitation sent to {$target->username}!");
        } catch (InvalidArgumentException $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['invite' => $e->getMessage()]);
        }
    }

    public function buildRoomStatePayload(GameRoom $room, User $viewer): array
    {
        $session = $room->activeSession;
        $boardState = $session?->board_state;

        if (($boardState['type'] ?? '') === 'rock-paper-scissors' && !empty($boardState['current_choices'])) {
            $masked = [];
            foreach ($boardState['current_choices'] as $uid => $choice) {
                $masked[$uid] = ((int) $uid === (int) $viewer->id) ? $choice : 'locked';
            }
            $boardState['current_choices'] = $masked;
        }

        return [
            'room_code' => $room->room_code,
            'name' => $room->name,
            'status' => $room->status,
            'visibility' => $room->visibility,
            'host_user_id' => (int) $room->host_user_id,
            'max_players' => (int) $room->max_players,
            'players' => $room->roomPlayers->map(fn ($rp) => [
                'user_id' => (int) $rp->user_id,
                'name' => $rp->user?->name,
                'username' => $rp->user?->username,
                'avatar_url' => $rp->user?->avatar_url,
                'slot' => (int) $rp->player_slot,
                'symbol' => $rp->player_symbol,
                'status' => $rp->status,
                'score' => (int) $rp->score,
            ])->values()->all(),
            'session' => $session ? [
                'id' => (int) $session->id,
                'session_number' => (int) $session->session_number,
                'status' => $session->status,
                'current_turn_user_id' => $session->current_turn_user_id ? (int) $session->current_turn_user_id : null,
                'winner_user_id' => $session->winner_user_id ? (int) $session->winner_user_id : null,
                'is_draw' => (bool) $session->is_draw,
                'result_reason' => $session->result_reason,
                'board_state' => $boardState,
                'moves_count' => (int) $session->moves_count,
                'moves' => $session->moves->map(fn ($m) => [
                    'id' => (int) $m->id,
                    'user_id' => (int) $m->user_id,
                    'username' => $m->user?->username,
                    'move_number' => (int) $m->move_number,
                    'move_data' => $m->move_data,
                ])->values()->all(),
            ] : null,
        ];
    }
}
