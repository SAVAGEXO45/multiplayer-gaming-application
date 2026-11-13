<?php

namespace App\Services\Games;

use App\Models\GameSession;
use App\Services\Games\Contracts\GameEngineInterface;
use InvalidArgumentException;

class TicTacToeEngine implements GameEngineInterface
{
    private const WIN_LINES = [
        [0, 1, 2], [3, 4, 5], [6, 7, 8], // Rows
        [0, 3, 6], [1, 4, 7], [2, 5, 8], // Columns
        [0, 4, 8], [2, 4, 6],           // Diagonals
    ];

    public function initializeBoard(array $players): array
    {
        $symbols = [];
        foreach ($players as $player) {
            $symbols[$player['user_id']] = $player['slot'] === 1 ? 'X' : 'O';
        }

        return [
            'type' => 'tic-tac-toe',
            'cells' => array_fill(0, 9, null),
            'symbols' => $symbols,
            'winning_line' => null,
            'last_move' => null,
        ];
    }

    public function applyMove(GameSession $session, int $userId, array $moveData, array $players): array
    {
        if ($session->status !== 'in_progress') {
            throw new InvalidArgumentException('This game session has already ended.');
        }

        if ((int) $session->current_turn_user_id !== $userId) {
            throw new InvalidArgumentException('It is not your turn to make a move.');
        }

        if (!isset($moveData['index']) || !is_numeric($moveData['index'])) {
            throw new InvalidArgumentException('Move index is required.');
        }

        $index = (int) $moveData['index'];
        if ($index < 0 || $index > 8) {
            throw new InvalidArgumentException('Cell index must be between 0 and 8.');
        }

        $board = $session->board_state;
        $cells = $board['cells'] ?? array_fill(0, 9, null);

        if ($cells[$index] !== null) {
            throw new InvalidArgumentException('That cell is already occupied.');
        }

        $symbol = $board['symbols'][$userId] ?? ($this->getPlayerSlot($players, $userId) === 1 ? 'X' : 'O');
        $cells[$index] = $symbol;

        $winningLine = $this->findWinningLine($cells, $symbol);
        $isFull = !in_array(null, $cells, true);

        $status = 'in_progress';
        $winnerUserId = null;
        $isDraw = false;
        $resultReason = null;
        $nextTurnUserId = $this->getOpponentUserId($players, $userId);

        if ($winningLine !== null) {
            $status = 'completed';
            $winnerUserId = $userId;
            $nextTurnUserId = null;
            $resultReason = "Three {$symbol}'s in a row";
        } elseif ($isFull) {
            $status = 'completed';
            $isDraw = true;
            $nextTurnUserId = null;
            $resultReason = 'All 9 cells filled with no three-in-a-row';
        }

        $board['cells'] = $cells;
        $board['winning_line'] = $winningLine;
        $board['last_move'] = [
            'user_id' => $userId,
            'index' => $index,
            'symbol' => $symbol,
        ];

        return [
            'board_state' => $board,
            'next_turn_user_id' => $nextTurnUserId,
            'status' => $status,
            'winner_user_id' => $winnerUserId,
            'is_draw' => $isDraw,
            'result_reason' => $resultReason,
            'sanitized_move' => [
                'index' => $index,
                'symbol' => $symbol,
            ],
        ];
    }

    private function findWinningLine(array $cells, string $symbol): ?array
    {
        foreach (self::WIN_LINES as $line) {
            if (
                $cells[$line[0]] === $symbol &&
                $cells[$line[1]] === $symbol &&
                $cells[$line[2]] === $symbol
            ) {
                return $line;
            }
        }

        return null;
    }

    private function getPlayerSlot(array $players, int $userId): int
    {
        foreach ($players as $player) {
            if ((int) $player['user_id'] === $userId) {
                return (int) $player['slot'];
            }
        }

        return 1;
    }

    private function getOpponentUserId(array $players, int $userId): ?int
    {
        foreach ($players as $player) {
            if ((int) $player['user_id'] !== $userId) {
                return (int) $player['user_id'];
            }
        }

        return null;
    }
}
