<?php

namespace App\Services\Games\Contracts;

use App\Models\GameSession;

interface GameEngineInterface
{
    /**
     * Generate the initial board state when a session starts.
     *
     * @param array<int, array{user_id: int, slot: int, symbol: string}> $players
     * @return array<string, mixed>
     */
    public function initializeBoard(array $players): array;

    /**
     * Validate and apply a player's move to the current session.
     *
     * @param GameSession $session
     * @param int $userId
     * @param array<string, mixed> $moveData
     * @param array<int, array{user_id: int, slot: int, symbol: string}> $players
     * @return array{
     *     board_state: array<string, mixed>,
     *     next_turn_user_id: int|null,
     *     status: string,
     *     winner_user_id: int|null,
     *     is_draw: bool,
     *     result_reason: string|null,
     *     sanitized_move: array<string, mixed>
     * }
     */
    public function applyMove(GameSession $session, int $userId, array $moveData, array $players): array;
}
