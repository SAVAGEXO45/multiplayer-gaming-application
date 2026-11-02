<?php

namespace App\Services;

use App\Enums\RoomStatus;
use App\Events\RoomUpdated;
use App\Models\Game;
use App\Models\GameRoom;
use App\Models\RoomPlayer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class RoomService
{
    public function __construct(
        private readonly GameService $games,
        private readonly SecurityEventService $security,
    ) {}

    /**
     * @param  array{name: string, max_players: int, is_private?: bool, password?: string|null}  $data
     */
    public function create(User $host, Game $game, array $data): GameRoom
    {
        if (! $game->is_active) {
            throw ValidationException::withMessages(['game' => 'This game is not available.']);
        }

        if (! $game->supports_multiplayer) {
            throw ValidationException::withMessages(['game' => 'This game does not support multiplayer rooms.']);
        }

        $maxPlayers = (int) $data['max_players'];
        $isPrivate = (bool) ($data['is_private'] ?? false);

        if ($maxPlayers < $game->min_players || $maxPlayers > $game->max_players) {
            throw ValidationException::withMessages([
                'max_players' => "Choose between {$game->min_players} and {$game->max_players} players.",
            ]);
        }

        if ($isPrivate && empty($data['password'])) {
            throw ValidationException::withMessages(['password' => 'Private rooms need a password.']);
        }

        return DB::transaction(function () use ($host, $game, $data, $maxPlayers, $isPrivate): GameRoom {
            $room = GameRoom::create([
                'code' => $this->uniqueCode(),
                'name' => $data['name'],
                'game_id' => $game->id,
                'host_id' => $host->id,
                'max_players' => $maxPlayers,
                'status' => RoomStatus::Waiting,
                'is_private' => $isPrivate,
                'password' => $isPrivate ? Hash::make($data['password']) : null,
            ]);

            $this->addPlayer($room, $host, true);

            $this->dispatch($room);

            return $room->fresh(['game', 'host', 'activePlayers.user']);
        });
    }

    public function join(User $user, GameRoom $room, ?string $password = null): GameRoom
    {
        $room->loadMissing('game', 'activePlayers');

        if ($room->status !== RoomStatus::Waiting) {
            $this->security->log('room.join_denied', 'Attempted to join a room that is not waiting.', $user, 'medium', [
                'room_id' => $room->id,
                'status' => $room->status->value,
            ], request());

            throw ValidationException::withMessages(['room' => 'This room is no longer accepting players.']);
        }

        $existing = $room->players()->where('user_id', $user->id)->first();

        if ($existing !== null && $existing->left_at === null) {
            return $room->fresh(['game', 'host', 'activePlayers.user']);
        }

        if ($room->isFull()) {
            throw ValidationException::withMessages(['room' => 'This room is full.']);
        }

        if ($room->hasPassword() && ! Hash::check((string) $password, $room->password)) {
            $this->security->log('room.bad_password', 'Wrong password supplied for a private room.', $user, 'medium', [
                'room_id' => $room->id,
            ], request());

            throw ValidationException::withMessages(['password' => 'The room password is incorrect.']);
        }

        return DB::transaction(function () use ($room, $user, $existing): GameRoom {
            if ($existing !== null) {
                $existing->update(['left_at' => null, 'joined_at' => now(), 'is_ready' => false]);
            } else {
                $this->addPlayer($room, $user, false);
            }

            $this->dispatch($room);

            return $room->fresh(['game', 'host', 'activePlayers.user']);
        });
    }

    public function leave(User $user, GameRoom $room): GameRoom
    {
        $player = $room->activePlayers()->where('user_id', $user->id)->first();

        if ($player === null) {
            throw ValidationException::withMessages(['room' => 'You are not in this room.']);
        }

        if ($room->status === RoomStatus::InProgress && $room->current_session_id !== null) {
            $session = $room->currentSession;

            if ($session !== null && $session->isActive() && $session->involves($user)) {
                $this->games->forfeit($session, $user);
            }
        }

        return DB::transaction(function () use ($room, $user, $player): GameRoom {
            $player->update(['left_at' => now(), 'is_ready' => false]);

            $remaining = $room->activePlayers()->count();

            if ($remaining === 0) {
                $room->update(['status' => RoomStatus::Closed, 'finished_at' => now()]);
            } elseif ($room->host_id === $user->id) {
                $nextHost = $room->activePlayers()->orderBy('joined_at')->first();
                $room->update(['host_id' => $nextHost->user_id]);
                $room->players()->where('user_id', $nextHost->user_id)->update(['is_host' => true]);
                $room->players()->where('user_id', $user->id)->update(['is_host' => false]);
            }

            $this->dispatch($room);

            return $room->fresh(['game', 'host', 'activePlayers.user']);
        });
    }

    public function toggleReady(User $user, GameRoom $room): GameRoom
    {
        if ($room->status !== RoomStatus::Waiting) {
            throw ValidationException::withMessages(['room' => 'Readiness can only change in the waiting room.']);
        }

        $player = $room->activePlayers()->where('user_id', $user->id)->first();

        if ($player === null) {
            throw ValidationException::withMessages(['room' => 'You are not in this room.']);
        }

        $player->update(['is_ready' => ! $player->is_ready]);

        $this->dispatch($room);

        return $room->fresh(['game', 'host', 'activePlayers.user']);
    }

    public function start(User $user, GameRoom $room): GameRoom
    {
        $room->loadMissing('game', 'activePlayers');

        if ($room->host_id !== $user->id) {
            throw ValidationException::withMessages(['room' => 'Only the host can start the game.']);
        }

        if ($room->status !== RoomStatus::Waiting) {
            throw ValidationException::withMessages(['room' => 'This room is already playing.']);
        }

        $players = $room->activePlayers()->get();

        if ($players->count() !== $room->max_players) {
            throw ValidationException::withMessages(['room' => 'Wait for the room to fill up before starting.']);
        }

        if ($players->contains(fn (RoomPlayer $player): bool => ! $player->is_ready)) {
            throw ValidationException::withMessages(['room' => 'Every player must be ready before the game starts.']);
        }

        $this->games->startForRoom($room);

        return $room->fresh(['game', 'host', 'activePlayers.user', 'currentSession']);
    }

    private function addPlayer(GameRoom $room, User $user, bool $isHost): RoomPlayer
    {
        return $room->players()->create([
            'user_id' => $user->id,
            'is_ready' => false,
            'is_host' => $isHost,
            'joined_at' => now(),
        ]);
    }

    private function uniqueCode(): string
    {
        do {
            $code = Str::upper(Str::random(6));
        } while (GameRoom::query()->where('code', $code)->exists());

        return $code;
    }

    private function dispatch(GameRoom $room): void
    {
        try {
            event(new RoomUpdated($room));
        } catch (Throwable) {
            // Polling is used as a local fallback when broadcasting is unavailable.
        }
    }
}
