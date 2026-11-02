<?php

namespace App\Services;

use App\Enums\GameResultType;
use App\Enums\GameSessionStatus;
use App\Enums\RoomStatus;
use App\Events\GameSessionUpdated;
use App\Events\RoomUpdated;
use App\Models\Game;
use App\Models\GameResult;
use App\Models\GameRoom;
use App\Models\GameSession;
use App\Models\User;
use App\Services\Games\GameManager;
use App\Services\Games\MoveResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class GameService
{
    public function __construct(
        private readonly GameManager $engines,
        private readonly ScoringService $scoring,
        private readonly PlayerStatisticsService $statistics,
        private readonly AchievementService $achievements,
    ) {}

    public function startSolo(User $user, Game $game, string $difficulty): GameSession
    {
        if (! $game->is_active) {
            throw ValidationException::withMessages(['game' => 'This game is not available right now.']);
        }

        if (! in_array($difficulty, $game->difficultyOptions(), true)) {
            throw ValidationException::withMessages(['difficulty' => 'Choose a valid difficulty.']);
        }

        $engine = $this->engines->for($game);

        return GameSession::create([
            'game_id' => $game->id,
            'created_by' => $user->id,
            'status' => GameSessionStatus::Active,
            'difficulty' => $difficulty,
            'state' => $engine->initialState([$user->id], $difficulty),
            'current_turn_user_id' => $user->id,
            'started_at' => now(),
        ]);
    }

    public function startForRoom(GameRoom $room): GameSession
    {
        $room->loadMissing('game', 'activePlayers');

        if ($room->status !== RoomStatus::Waiting) {
            throw ValidationException::withMessages(['room' => 'This room is not waiting for a new game.']);
        }

        $playerIds = $room->activePlayers()->orderBy('joined_at')->pluck('user_id')->map(fn ($id) => (int) $id)->all();

        if (count($playerIds) < $room->game->min_players) {
            throw ValidationException::withMessages(['room' => 'Not enough players to start.']);
        }

        if (count($playerIds) !== $room->max_players) {
            throw ValidationException::withMessages(['room' => 'The room must be full before the game can start.']);
        }

        $engine = $this->engines->for($room->game);

        return DB::transaction(function () use ($room, $playerIds, $engine): GameSession {
            $session = GameSession::create([
                'game_id' => $room->game_id,
                'room_id' => $room->id,
                'created_by' => $room->host_id,
                'status' => GameSessionStatus::Active,
                'difficulty' => 'medium',
                'state' => $engine->initialState($playerIds, 'medium'),
                'current_turn_user_id' => $playerIds[0],
                'started_at' => now(),
            ]);

            $room->update([
                'status' => RoomStatus::InProgress,
                'current_session_id' => $session->id,
                'started_at' => now(),
                'finished_at' => null,
            ]);

            $room->players()->update(['is_ready' => false, 'score' => 0]);

            $this->dispatchRoomUpdate($room);

            return $session;
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function submitMove(GameSession $session, User $user, array $payload): GameSession
    {
        return DB::transaction(function () use ($session, $user, $payload): GameSession {
            /** @var GameSession $locked */
            $locked = GameSession::query()->with('game')->lockForUpdate()->findOrFail($session->id);

            if ($locked->status !== GameSessionStatus::Active) {
                throw ValidationException::withMessages(['game' => 'This game has already finished.']);
            }

            if (! $locked->involves($user)) {
                throw ValidationException::withMessages(['game' => 'You are not a player in this game.']);
            }

            if ($locked->room_id !== null) {
                $room = GameRoom::query()->find($locked->room_id);

                if ($room !== null && $room->status !== RoomStatus::InProgress) {
                    throw ValidationException::withMessages(['game' => 'The room is no longer accepting moves.']);
                }
            }

            $result = $this->engines->for($locked->game)->move($locked, $user, $payload);

            $locked->state = $result->state;
            $locked->current_turn_user_id = $result->nextTurnUserId;

            if ($result->finished) {
                $this->applyFinish($locked, $result);
            } else {
                $locked->save();
            }

            $this->dispatchSessionUpdate($locked);

            return $locked->fresh(['game', 'results.user']);
        });
    }

    public function forfeit(GameSession $session, User $user): GameSession
    {
        return DB::transaction(function () use ($session, $user): GameSession {
            /** @var GameSession $locked */
            $locked = GameSession::query()->with('game')->lockForUpdate()->findOrFail($session->id);

            if ($locked->status !== GameSessionStatus::Active) {
                return $locked;
            }

            if (! $locked->involves($user)) {
                throw ValidationException::withMessages(['game' => 'You are not a player in this game.']);
            }

            $players = $locked->playerIds();
            $opponent = collect($players)->first(fn (int $id): bool => $id !== $user->id);

            if ($opponent === null) {
                $locked->update([
                    'status' => GameSessionStatus::Abandoned,
                    'finished_at' => now(),
                    'current_turn_user_id' => null,
                ]);

                return $locked;
            }

            $result = new MoveResult(
                $locked->state,
                true,
                $opponent,
                false,
                "{$user->username} forfeited the match.",
                null,
            );

            $this->applyFinish($locked, $result);
            $this->dispatchSessionUpdate($locked);

            return $locked->fresh(['game', 'results.user']);
        });
    }

    public function abandon(GameSession $session): void
    {
        if (! $session->isActive()) {
            return;
        }

        $session->update([
            'status' => GameSessionStatus::Abandoned,
            'finished_at' => now(),
            'current_turn_user_id' => null,
        ]);
    }

    private function applyFinish(GameSession $session, MoveResult $result): void
    {
        $session->state = $result->state;
        $session->status = GameSessionStatus::Finished;
        $session->winner_id = $result->winnerId;
        $session->is_draw = $result->isDraw;
        $session->finished_at = now();
        $session->current_turn_user_id = null;
        $session->save();

        $multiplayer = $session->room_id !== null;
        $engine = $this->engines->for($session->game);
        $players = User::query()->whereIn('id', $session->playerIds())->get()->keyBy('id');
        $fresh = $session->fresh();

        foreach ($session->playerIds() as $playerId) {
            $player = $players->get($playerId);

            if ($player === null) {
                continue;
            }

            $type = $result->isDraw
                ? GameResultType::Draw
                : ($result->winnerId === $playerId ? GameResultType::Win : GameResultType::Loss);

            $gameResult = GameResult::query()->firstOrCreate(
                ['game_session_id' => $session->id, 'user_id' => $playerId],
                [
                    'game_id' => $session->game_id,
                    'result' => $type,
                    'score' => $engine->performanceScore($fresh, $player),
                    'points_awarded' => 0,
                    'is_multiplayer' => $multiplayer,
                    'metadata' => [
                        'difficulty' => $session->difficulty,
                        'winner_id' => $result->winnerId,
                        'message' => $result->message,
                    ],
                ],
            );

            if (! $gameResult->wasRecentlyCreated) {
                continue;
            }

            $this->statistics->record($player, $gameResult, $multiplayer);

            $points = $this->pointsFor($session->game, $type, $multiplayer, $session->difficulty);
            $transaction = $this->scoring->award(
                $player,
                $points,
                $this->rewardReason($session->game, $type, $multiplayer),
                'game_result',
                $gameResult->id,
                null,
                ['game' => $session->game->slug, 'result' => $type->value],
            );

            $gameResult->update(['points_awarded' => $transaction?->amount ?? 0]);

            $this->achievements->evaluate($player);
        }

        if ($session->room_id !== null) {
            $room = GameRoom::query()->find($session->room_id);

            if ($room !== null) {
                $room->update([
                    'status' => RoomStatus::Waiting,
                    'finished_at' => now(),
                ]);
                $room->players()->update(['is_ready' => false]);
                $this->dispatchRoomUpdate($room);
            }
        }
    }

    private function pointsFor(Game $game, GameResultType $type, bool $multiplayer, string $difficulty): int
    {
        return match ($type) {
            GameResultType::Win => $multiplayer
                ? $game->points_win
                : (int) round($game->points_win * $this->difficultyMultiplier($difficulty)),
            GameResultType::Draw => $game->points_draw,
            GameResultType::Loss => $multiplayer ? $game->points_loss : $game->points_participation,
        };
    }

    private function difficultyMultiplier(string $difficulty): float
    {
        return match ($difficulty) {
            'easy' => 0.75,
            'hard' => 1.5,
            default => 1.0,
        };
    }

    private function rewardReason(Game $game, GameResultType $type, bool $multiplayer): string
    {
        $mode = $multiplayer ? 'Multiplayer' : 'Solo';

        return "{$mode} {$game->name}: {$type->value}";
    }

    private function dispatchSessionUpdate(GameSession $session): void
    {
        try {
            event(new GameSessionUpdated($session));
        } catch (Throwable) {
            // Broadcasting is optional in local development (polling fallback is used).
        }
    }

    private function dispatchRoomUpdate(GameRoom $room): void
    {
        try {
            event(new RoomUpdated($room));
        } catch (Throwable) {
            // Broadcasting is optional in local development (polling fallback is used).
        }
    }
}
