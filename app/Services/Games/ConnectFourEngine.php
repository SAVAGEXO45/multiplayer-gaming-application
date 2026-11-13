<?php

namespace App\Services\Games;

use App\Models\GameSession;
use App\Services\Games\Contracts\GameEngineInterface;
use InvalidArgumentException;

class ConnectFourEngine implements GameEngineInterface
{
    private const ROWS = 6;
    private const COLS = 7;

    public function initializeBoard(array $players): array
    {
        $grid = [];
        for ($r = 0; $r < self::ROWS; $r++) {
            $grid[$r] = array_fill(0, self::COLS, null);
        }

        $colors = [];
        foreach ($players as $player) {
            $colors[$player['user_id']] = $player['slot'] === 1 ? 'R' : 'Y';
        }

        return [
            'type' => 'connect-four',
            'rows' => self::ROWS,
            'cols' => self::COLS,
            'grid' => $grid,
            'colors' => $colors,
            'winning_cells' => null,
            'last_move' => null,
        ];
    }

    public function applyMove(GameSession $session, int $userId, array $moveData, array $players): array
    {
        if ($session->status !== 'in_progress') {
            throw new InvalidArgumentException('This game session has already ended.');
        }

        if ((int) $session->current_turn_user_id !== $userId) {
            throw new InvalidArgumentException('It is not your turn to drop a disc.');
        }

        if (!isset($moveData['column']) || !is_numeric($moveData['column'])) {
            throw new InvalidArgumentException('Column number is required.');
        }

        $col = (int) $moveData['column'];
        if ($col < 0 || $col >= self::COLS) {
            throw new InvalidArgumentException('Column must be between 0 and 6.');
        }

        $board = $session->board_state;
        $grid = $board['grid'];

        // Find lowest empty row in column (row 5 down to 0)
        $targetRow = null;
        for ($r = self::ROWS - 1; $r >= 0; $r--) {
            if ($grid[$r][$col] === null) {
                $targetRow = $r;
                break;
            }
        }

        if ($targetRow === null) {
            throw new InvalidArgumentException('Selected column is already full.');
        }

        $color = $board['colors'][$userId] ?? 'R';
        $grid[$targetRow][$col] = $color;

        $winningCells = $this->findConnectFour($grid, $targetRow, $col, $color);
        $isFull = $this->isGridFull($grid);

        $status = 'in_progress';
        $winnerUserId = null;
        $isDraw = false;
        $resultReason = null;
        $nextTurnUserId = $this->getOpponentUserId($players, $userId);

        if ($winningCells !== null) {
            $status = 'completed';
            $winnerUserId = $userId;
            $nextTurnUserId = null;
            $colorLabel = $color === 'R' ? 'Crimson' : 'Cyber Yellow';
            $resultReason = "Connected four {$colorLabel} discs";
        } elseif ($isFull) {
            $status = 'completed';
            $isDraw = true;
            $nextTurnUserId = null;
            $resultReason = 'Grid completely filled with no four-in-a-row';
        }

        $board['grid'] = $grid;
        $board['winning_cells'] = $winningCells;
        $board['last_move'] = [
            'user_id' => $userId,
            'row' => $targetRow,
            'column' => $col,
            'color' => $color,
        ];

        return [
            'board_state' => $board,
            'next_turn_user_id' => $nextTurnUserId,
            'status' => $status,
            'winner_user_id' => $winnerUserId,
            'is_draw' => $isDraw,
            'result_reason' => $resultReason,
            'sanitized_move' => [
                'row' => $targetRow,
                'column' => $col,
                'color' => $color,
            ],
        ];
    }

    private function findConnectFour(array $grid, int $row, int $col, string $color): ?array
    {
        $directions = [
            [[0, -1], [0, 1]],   // Horizontal
            [[-1, 0], [1, 0]],   // Vertical
            [[-1, -1], [1, 1]],  // Diagonal \
            [[-1, 1], [1, -1]],  // Diagonal /
        ];

        foreach ($directions as $dirPair) {
            $cells = [[$row, $col]];
            foreach ($dirPair as [$dr, $dc]) {
                $r = $row + $dr;
                $c = $col + $dc;
                while (
                    $r >= 0 && $r < self::ROWS &&
                    $c >= 0 && $c < self::COLS &&
                    $grid[$r][$c] === $color
                ) {
                    $cells[] = [$r, $c];
                    $r += $dr;
                    $c += $dc;
                }
            }

            if (count($cells) >= 4) {
                return array_slice($cells, 0, 4);
            }
        }

        return null;
    }

    private function isGridFull(array $grid): bool
    {
        for ($c = 0; $c < self::COLS; $c++) {
            if ($grid[0][$c] === null) {
                return false;
            }
        }

        return true;
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
