<?php

namespace App\Services;

use App\Events\GameMoveMade;
use App\Events\MatchCompleted;
use App\Events\RoomUpdated;
use App\Models\Game;
use App\Models\GameMove;
use App\Models\GameRoom;
use App\Models\GameSession;
use App\Models\RoomPlayer;
use App\Models\User;
use App\Services\Games\GameEngineFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

class RoomService
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    public function createRoom(User $host, Game $game, array $data): GameRoom
    {
        if (!$game->is_active) {
            throw new InvalidArgumentException('This game is currently unavailable.');
        }

        return DB::transaction(function () use ($host, $game, $data) {
            $roomCode = $this->generateUniqueRoomCode();

            $room = GameRoom::create([
                'room_code' => $roomCode,
                'name' => $data['name'] ?? ($host->username . "'s " . $game->name . ' Arena'),
                'game_id' => $game->id,
                'host_user_id' => $host->id,
                'visibility' => in_array($data['visibility'] ?? 'public', ['public', 'private'], true)
                    ? $data['visibility']
                    : 'public',
                'status' => 'waiting',
                'max_players' => $game->max_players,
                'settings' => $data['settings'] ?? [
                    'auto_start' => true,
                    'allow_spectators' => true,
                ],
            ]);

            RoomPlayer::create([
                'game_room_id' => $room->id,
                'user_id' => $host->id,
                'player_slot' => 1,
                'player_symbol' => $this->resolveSymbolForSlot($game->slug, 1),
                'status' => 'active',
                'is_ready' => true,
                'score' => 0,
                'joined_at' => now(),
                'last_ping_at' => now(),
            ]);

            $this->safeBroadcast(new RoomUpdated($room->fresh(['game', 'host', 'roomPlayers.user', 'activeSession']), 'created'));

            return $room;
        });
    }

    public function joinRoom(GameRoom $room, User $user): GameRoom
    {
        return DB::transaction(function () use ($room, $user) {
            $room = GameRoom::where('id', $room->id)->lockForUpdate()->firstOrFail();
            $room->load(['game', 'roomPlayers']);

            if (in_array($room->status, ['completed', 'cancelled'], true)) {
                throw new InvalidArgumentException('This game room has already closed.');
            }

            $existingPlayer = $room->roomPlayers->firstWhere('user_id', $user->id);

            if ($existingPlayer) {
                if (in_array($existingPlayer->status, ['disconnected', 'left'], true)) {
                    $activeCount = $room->roomPlayers->whereIn('status', ['active', 'ready'])->count();
                    if ($existingPlayer->status === 'left' && $activeCount >= $room->max_players) {
                        throw new InvalidArgumentException('This room is already full.');
                    }

                    $existingPlayer->update([
                        'status' => 'active',
                        'is_ready' => true,
                        'last_ping_at' => now(),
                    ]);

                    $this->safeBroadcast(new RoomUpdated($room->fresh(['game', 'host', 'roomPlayers.user', 'activeSession']), 'reconnected'));
                }

                return $room->fresh(['game', 'host', 'roomPlayers.user', 'activeSession']);
            }

            $activePlayers = $room->roomPlayers->whereIn('status', ['active', 'ready']);
            if ($activePlayers->count() >= $room->max_players) {
                throw new InvalidArgumentException('This room is already full.');
            }

            $occupiedSlots = $activePlayers->pluck('player_slot')->map(fn ($s) => (int) $s)->all();
            $assignedSlot = 1;
            for ($slot = 1; $slot <= $room->max_players; $slot++) {
                if (!in_array($slot, $occupiedSlots, true)) {
                    $assignedSlot = $slot;
                    break;
                }
            }

            RoomPlayer::create([
                'game_room_id' => $room->id,
                'user_id' => $user->id,
                'player_slot' => $assignedSlot,
                'player_symbol' => $this->resolveSymbolForSlot($room->game->slug, $assignedSlot),
                'status' => 'active',
                'is_ready' => true,
                'score' => 0,
                'joined_at' => now(),
                'last_ping_at' => now(),
            ]);

            $room->refresh();
            $activeCountAfterJoin = $room->activePlayers()->count();

            $autoStart = $room->settings['auto_start'] ?? true;
            if ($room->status === 'waiting' && $activeCountAfterJoin >= $room->game->min_players && $autoStart) {
                $this->startNewSession($room);
            } else {
                $this->safeBroadcast(new RoomUpdated($room->fresh(['game', 'host', 'roomPlayers.user', 'activeSession']), 'player_joined'));
            }

            return $room->fresh(['game', 'host', 'roomPlayers.user', 'activeSession']);
        });
    }

    public function startNewSession(GameRoom $room, ?User $actor = null): GameSession
    {
        return DB::transaction(function () use ($room, $actor) {
            $room = GameRoom::where('id', $room->id)->lockForUpdate()->firstOrFail();
            $room->load(['game', 'roomPlayers.user', 'activeSession']);

            if ($actor !== null && !$room->isHost($actor->id) && !$actor->isAdmin()) {
                $isMember = $room->hasPlayer($actor->id);
                if (!$isMember) {
                    throw new InvalidArgumentException('Only room participants or the room owner can start or restart the game.');
                }
            }

            $activePlayers = $room->roomPlayers
                ->whereIn('status', ['active', 'ready'])
                ->sortBy('player_slot')
                ->values();

            if ($activePlayers->count() < $room->game->min_players) {
                throw new InvalidArgumentException("At least {$room->game->min_players} active players are required to start {$room->game->name}.");
            }

            // Close any previous in-progress session if restarting
            GameSession::where('game_room_id', $room->id)
                ->where('status', 'in_progress')
                ->update([
                    'status' => 'abandoned',
                    'ended_at' => now(),
                    'result_reason' => 'Session restarted',
                ]);

            $lastSessionNumber = (int) GameSession::where('game_room_id', $room->id)->max('session_number');
            $nextSessionNumber = $lastSessionNumber + 1;

            // Alternate starting player on rematches for fairness
            $playersPayload = $activePlayers->map(fn (RoomPlayer $rp) => [
                'user_id' => (int) $rp->user_id,
                'slot' => (int) $rp->player_slot,
                'symbol' => (string) $rp->player_symbol,
            ])->all();

            $engine = GameEngineFactory::make($room->game->slug);
            $boardState = $engine->initializeBoard($playersPayload);

            $firstTurnPlayer = $activePlayers->firstWhere('player_slot', 1) ?? $activePlayers->first();

            $session = GameSession::create([
                'game_room_id' => $room->id,
                'game_id' => $room->game_id,
                'session_number' => $nextSessionNumber,
                'status' => 'in_progress',
                'current_turn_user_id' => $firstTurnPlayer->user_id,
                'winner_user_id' => null,
                'is_draw' => false,
                'board_state' => $boardState,
                'scores' => $activePlayers->mapWithKeys(fn ($rp) => [$rp->user_id => $rp->score])->all(),
                'moves_count' => 0,
                'started_at' => now(),
            ]);

            $room->update([
                'status' => 'in_progress',
                'started_at' => $room->started_at ?? now(),
                'ended_at' => null,
            ]);

            $this->safeBroadcast(new RoomUpdated($room->fresh(['game', 'host', 'roomPlayers.user', 'activeSession']), 'game_started'));

            return $session;
        });
    }

    public function submitMove(GameRoom $room, User $user, array $moveData): array
    {
        return DB::transaction(function () use ($room, $user, $moveData) {
            $room = GameRoom::where('id', $room->id)->lockForUpdate()->firstOrFail();
            $room->load(['game', 'roomPlayers.user']);

            $playerMembership = $room->roomPlayers
                ->where('user_id', $user->id)
                ->whereIn('status', ['active', 'ready'])
                ->first();

            if (!$playerMembership) {
                throw new InvalidArgumentException('Unauthorized: You are not an active member of this game room.');
            }

            $playerMembership->update(['last_ping_at' => now()]);

            $session = GameSession::where('game_room_id', $room->id)
                ->where('status', 'in_progress')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (!$session) {
                throw new InvalidArgumentException('There is no active game session in progress.');
            }

            $playersPayload = $room->roomPlayers
                ->whereIn('status', ['active', 'ready', 'disconnected'])
                ->sortBy('player_slot')
                ->values()
                ->map(fn (RoomPlayer $rp) => [
                    'user_id' => (int) $rp->user_id,
                    'slot' => (int) $rp->player_slot,
                    'symbol' => (string) $rp->player_symbol,
                ])
                ->all();

            $engine = GameEngineFactory::make($room->game->slug);
            $result = $engine->applyMove($session, $user->id, $moveData, $playersPayload);

            $nextMoveNumber = $session->moves_count + 1;

            $move = GameMove::create([
                'game_session_id' => $session->id,
                'user_id' => $user->id,
                'move_number' => $nextMoveNumber,
                'move_data' => $result['sanitized_move'],
                'board_state_after' => $result['board_state'],
            ]);

            $sessionCompleted = $result['status'] === 'completed';

            $session->update([
                'board_state' => $result['board_state'],
                'current_turn_user_id' => $result['next_turn_user_id'],
                'status' => $result['status'],
                'winner_user_id' => $result['winner_user_id'],
                'is_draw' => $result['is_draw'],
                'result_reason' => $result['result_reason'],
                'moves_count' => $nextMoveNumber,
                'ended_at' => $sessionCompleted ? now() : null,
            ]);

            if ($sessionCompleted) {
                event(new MatchCompleted($session->fresh(['game', 'room.roomPlayers.user'])));
                $room->refresh();
                $session->update([
                    'scores' => $room->roomPlayers->mapWithKeys(fn ($rp) => [$rp->user_id => $rp->score])->all(),
                ]);
            }

            $move->load('user');
            $this->safeBroadcast(new GameMoveMade($room->fresh(), $session->fresh(), $move));

            if ($sessionCompleted) {
                $this->safeBroadcast(new RoomUpdated($room->fresh(['game', 'host', 'roomPlayers.user', 'activeSession']), 'match_completed'));
            }

            return [
                'room' => $room->fresh(['game', 'host', 'roomPlayers.user', 'activeSession']),
                'session' => $session->fresh(['winner', 'currentTurnUser']),
                'move' => $move,
            ];
        });
    }

    public function leaveRoom(GameRoom $room, User $user): void
    {
        DB::transaction(function () use ($room, $user) {
            $room = GameRoom::where('id', $room->id)->lockForUpdate()->firstOrFail();
            $room->load(['game', 'roomPlayers', 'activeSession']);

            $membership = $room->roomPlayers->firstWhere('user_id', $user->id);
            if (!$membership) {
                return;
            }

            $membership->update([
                'status' => 'left',
                'is_ready' => false,
            ]);

            $remainingPlayers = $room->roomPlayers
                ->where('user_id', '!=', $user->id)
                ->whereIn('status', ['active', 'ready'])
                ->sortBy('player_slot')
                ->values();

            // If an active game session was running, award victory by forfeit to the remaining active player
            $activeSession = $room->activeSession;
            if ($activeSession && $activeSession->status === 'in_progress') {
                $winner = $remainingPlayers->first();
                $activeSession->update([
                    'status' => 'completed',
                    'winner_user_id' => $winner?->user_id,
                    'is_draw' => $winner === null,
                    'current_turn_user_id' => null,
                    'result_reason' => "{$user->username} left the room (Forfeit)",
                    'ended_at' => now(),
                ]);

                if ($winner) {
                    event(new MatchCompleted($activeSession->fresh(['game', 'room.roomPlayers.user'])));
                }
            }

            if ($remainingPlayers->isEmpty()) {
                $room->update([
                    'status' => 'completed',
                    'ended_at' => now(),
                ]);
            } else {
                // Reassign host if the host left
                if ((int) $room->host_user_id === (int) $user->id) {
                    $newHost = $remainingPlayers->first();
                    $room->update([
                        'host_user_id' => $newHost->user_id,
                        'status' => 'waiting',
                    ]);
                } else {
                    $room->update(['status' => 'waiting']);
                }
            }

            $this->safeBroadcast(new RoomUpdated($room->fresh(['game', 'host', 'roomPlayers.user', 'activeSession']), 'player_left', [
                'left_user_id' => $user->id,
                'left_username' => $user->username,
            ]));
        });
    }

    public function handlePlayerDisconnected(GameRoom $room, User $user): void
    {
        DB::transaction(function () use ($room, $user) {
            $membership = RoomPlayer::where('game_room_id', $room->id)
                ->where('user_id', $user->id)
                ->first();

            if (!$membership || $membership->status === 'left') {
                return;
            }

            $membership->update([
                'status' => 'disconnected',
                'last_ping_at' => now(),
            ]);

            $this->safeBroadcast(new RoomUpdated($room->fresh(['game', 'host', 'roomPlayers.user', 'activeSession']), 'player_disconnected', [
                'user_id' => $user->id,
                'username' => $user->username,
            ]));
        });
    }

    public function invitePlayer(GameRoom $room, User $sender, User $target): void
    {
        if ($sender->id === $target->id) {
            throw new InvalidArgumentException('You cannot invite yourself.');
        }

        $room->loadMissing('game');

        $this->notificationService->notify(
            $target,
            'room_invite',
            "Game Invite: {$room->game->name}",
            "{$sender->username} invited you to join room #{$room->room_code} ({$room->name}).",
            route('rooms.show', $room->room_code),
            [
                'room_code' => $room->room_code,
                'game_name' => $room->game->name,
                'sender_username' => $sender->username,
            ]
        );
    }

    private function generateUniqueRoomCode(): string
    {
        do {
            $code = Str::upper(Str::random(6));
        } while (GameRoom::where('room_code', $code)->exists());

        return $code;
    }

    private function resolveSymbolForSlot(string $gameSlug, int $slot): string
    {
        return match ($gameSlug) {
            'tic-tac-toe' => $slot === 1 ? 'X' : 'O',
            'connect-four' => $slot === 1 ? 'R' : 'Y',
            'chess' => $slot === 1 ? 'w' : 'b',
            'rock-paper-scissors' => $slot === 1 ? 'P1' : 'P2',
            default => 'P' . $slot,
        };
    }

    private function safeBroadcast(object $event): void
    {
        try {
            broadcast($event);
        } catch (Throwable) {
            // Gracefully continue if WebSocket server is not running
        }
    }
}
