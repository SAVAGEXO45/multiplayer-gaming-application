<?php

namespace App\Services\Games;

use App\Models\GameSession;
use App\Services\Games\Contracts\GameEngineInterface;
use InvalidArgumentException;

class ChessEngine implements GameEngineInterface
{
    public function initializeBoard(array $players): array
    {
        // Row 0 = Rank 8 (Black), Row 7 = Rank 1 (White)
        $grid = [
            ['bR', 'bN', 'bB', 'bQ', 'bK', 'bB', 'bN', 'bR'],
            ['bP', 'bP', 'bP', 'bP', 'bP', 'bP', 'bP', 'bP'],
            [null, null, null, null, null, null, null, null],
            [null, null, null, null, null, null, null, null],
            [null, null, null, null, null, null, null, null],
            [null, null, null, null, null, null, null, null],
            ['wP', 'wP', 'wP', 'wP', 'wP', 'wP', 'wP', 'wP'],
            ['wR', 'wN', 'wB', 'wQ', 'wK', 'wB', 'wN', 'wR'],
        ];

        $colors = [];
        foreach ($players as $player) {
            $colors[$player['user_id']] = $player['slot'] === 1 ? 'w' : 'b';
        }

        return [
            'type' => 'chess',
            'grid' => $grid,
            'colors' => $colors,
            'active_color' => 'w',
            'captured' => [
                'w' => [],
                'b' => [],
            ],
            'in_check' => null,
            'halfmove_clock' => 0,
            'fullmove_number' => 1,
            'last_move' => null,
        ];
    }

    public function applyMove(GameSession $session, int $userId, array $moveData, array $players): array
    {
        if ($session->status !== 'in_progress') {
            throw new InvalidArgumentException('This game session has already ended.');
        }

        if ((int) $session->current_turn_user_id !== $userId) {
            throw new InvalidArgumentException('It is not your turn to move.');
        }

        $board = $session->board_state;
        $grid = $board['grid'];
        $playerColor = $board['colors'][$userId] ?? 'w';
        $opponentColor = $playerColor === 'w' ? 'b' : 'w';
        $opponentUserId = $this->getOpponentUserId($players, $userId);

        if (
            !isset($moveData['from_row'], $moveData['from_col'], $moveData['to_row'], $moveData['to_col']) ||
            !is_numeric($moveData['from_row']) || !is_numeric($moveData['from_col']) ||
            !is_numeric($moveData['to_row']) || !is_numeric($moveData['to_col'])
        ) {
            throw new InvalidArgumentException('Chess move requires from_row, from_col, to_row, and to_col coordinates.');
        }

        $fr = (int) $moveData['from_row'];
        $fc = (int) $moveData['from_col'];
        $tr = (int) $moveData['to_row'];
        $tc = (int) $moveData['to_col'];

        if ($fr < 0 || $fr > 7 || $fc < 0 || $fc > 7 || $tr < 0 || $tr > 7 || $tc < 0 || $tc > 7) {
            throw new InvalidArgumentException('Coordinates must be within the 8x8 board (0-7).');
        }

        if ($fr === $tr && $fc === $tc) {
            throw new InvalidArgumentException('Source and destination squares cannot be identical.');
        }

        $piece = $grid[$fr][$fc];
        if ($piece === null || $piece[0] !== $playerColor) {
            throw new InvalidArgumentException('You must select one of your own pieces.');
        }

        $targetPiece = $grid[$tr][$tc];
        if ($targetPiece !== null && $targetPiece[0] === $playerColor) {
            throw new InvalidArgumentException('You cannot capture your own piece.');
        }

        if (!$this->isValidPieceMove($grid, $fr, $fc, $tr, $tc, $piece)) {
            throw new InvalidArgumentException('Illegal movement pattern for ' . $this->getPieceName($piece[1]) . '.');
        }

        // Simulate move on board
        $nextGrid = $grid;
        $nextGrid[$tr][$tc] = $piece;
        $nextGrid[$fr][$fc] = null;

        // Pawn promotion to Queen (or requested piece)
        $promoted = false;
        if ($piece[1] === 'P' && ($tr === 0 || $tr === 7)) {
            $promoType = strtoupper((string) ($moveData['promotion'] ?? 'Q'));
            if (!in_array($promoType, ['Q', 'R', 'B', 'N'], true)) {
                $promoType = 'Q';
            }
            $nextGrid[$tr][$tc] = $playerColor . $promoType;
            $promoted = true;
        }

        // If the move did not capture the opponent's King directly, verify the player did not leave their own King in check
        $opponentKingCaptured = $targetPiece === ($opponentColor . 'K');
        if (!$opponentKingCaptured && $this->isKingInCheck($nextGrid, $playerColor)) {
            throw new InvalidArgumentException('Illegal move: your King cannot remain in or move into check.');
        }

        if ($targetPiece !== null) {
            $board['captured'][$playerColor][] = $targetPiece;
            $board['halfmove_clock'] = 0;
        } elseif ($piece[1] === 'P') {
            $board['halfmove_clock'] = 0;
        } else {
            $board['halfmove_clock'] = ($board['halfmove_clock'] ?? 0) + 1;
        }

        if ($playerColor === 'b') {
            $board['fullmove_number'] = ($board['fullmove_number'] ?? 1) + 1;
        }

        $notation = $this->formatAlgebraicNotation($piece, $fr, $fc, $tr, $tc, $targetPiece !== null, $promoted);
        $board['grid'] = $nextGrid;
        $board['active_color'] = $opponentColor;

        $status = 'in_progress';
        $winnerUserId = null;
        $isDraw = false;
        $resultReason = null;
        $nextTurnUserId = $opponentUserId;

        if ($opponentKingCaptured) {
            $status = 'completed';
            $winnerUserId = $userId;
            $nextTurnUserId = null;
            $resultReason = 'Checkmate / Royal Capture by ' . ($playerColor === 'w' ? 'White' : 'Black');
        } else {
            $opponentInCheck = $this->isKingInCheck($nextGrid, $opponentColor);
            $board['in_check'] = $opponentInCheck ? $opponentColor : null;
            $opponentHasLegalMoves = $this->hasAnyLegalMoves($nextGrid, $opponentColor);

            if (!$opponentHasLegalMoves) {
                $status = 'completed';
                $nextTurnUserId = null;
                if ($opponentInCheck) {
                    $winnerUserId = $userId;
                    $resultReason = 'Checkmate by ' . ($playerColor === 'w' ? 'White' : 'Black');
                    $notation .= '#';
                } else {
                    $isDraw = true;
                    $resultReason = 'Stalemate — no legal moves available';
                }
            } elseif ($opponentInCheck) {
                $notation .= '+';
            } elseif ($board['halfmove_clock'] >= 100) {
                $status = 'completed';
                $isDraw = true;
                $nextTurnUserId = null;
                $resultReason = 'Draw by 50-move rule';
            }
        }

        $board['last_move'] = [
            'user_id' => $userId,
            'from' => [$fr, $fc],
            'to' => [$tr, $tc],
            'piece' => $piece,
            'captured' => $targetPiece,
            'notation' => $notation,
        ];

        return [
            'board_state' => $board,
            'next_turn_user_id' => $nextTurnUserId,
            'status' => $status,
            'winner_user_id' => $winnerUserId,
            'is_draw' => $isDraw,
            'result_reason' => $resultReason,
            'sanitized_move' => [
                'from_row' => $fr,
                'from_col' => $fc,
                'to_row' => $tr,
                'to_col' => $tc,
                'piece' => $piece,
                'captured' => $targetPiece,
                'notation' => $notation,
            ],
        ];
    }

    private function isValidPieceMove(array $grid, int $fr, int $fc, int $tr, int $tc, string $piece): bool
    {
        $color = $piece[0];
        $type = $piece[1];
        $dr = $tr - $fr;
        $dc = $tc - $fc;
        $absDr = abs($dr);
        $absDc = abs($dc);
        $target = $grid[$tr][$tc];

        switch ($type) {
            case 'P':
                $dir = $color === 'w' ? -1 : 1;
                $startRow = $color === 'w' ? 6 : 1;

                // 1 step forward
                if ($dc === 0 && $dr === $dir && $target === null) {
                    return true;
                }
                // 2 steps forward from starting rank
                if ($dc === 0 && $fr === $startRow && $dr === 2 * $dir && $target === null && $grid[$fr + $dir][$fc] === null) {
                    return true;
                }
                // Diagonal capture
                if ($absDc === 1 && $dr === $dir && $target !== null && $target[0] !== $color) {
                    return true;
                }
                return false;

            case 'N':
                return ($absDr === 2 && $absDc === 1) || ($absDr === 1 && $absDc === 2);

            case 'B':
                return $absDr === $absDc && $this->isPathClear($grid, $fr, $fc, $tr, $tc);

            case 'R':
                return ($dr === 0 || $dc === 0) && $this->isPathClear($grid, $fr, $fc, $tr, $tc);

            case 'Q':
                return ($dr === 0 || $dc === 0 || $absDr === $absDc) && $this->isPathClear($grid, $fr, $fc, $tr, $tc);

            case 'K':
                return $absDr <= 1 && $absDc <= 1;
        }

        return false;
    }

    private function isPathClear(array $grid, int $fr, int $fc, int $tr, int $tc): bool
    {
        $stepR = $tr <=> $fr;
        $stepC = $tc <=> $fc;
        $r = $fr + $stepR;
        $c = $fc + $stepC;

        while ($r !== $tr || $c !== $tc) {
            if ($grid[$r][$c] !== null) {
                return false;
            }
            $r += $stepR;
            $c += $stepC;
        }

        return true;
    }

    private function isKingInCheck(array $grid, string $kingColor): bool
    {
        $kr = null;
        $kc = null;
        $kingCode = $kingColor . 'K';

        for ($r = 0; $r < 8; $r++) {
            for ($c = 0; $c < 8; $c++) {
                if ($grid[$r][$c] === $kingCode) {
                    $kr = $r;
                    $kc = $c;
                    break 2;
                }
            }
        }

        if ($kr === null) {
            return true;
        }

        $enemyColor = $kingColor === 'w' ? 'b' : 'w';
        for ($r = 0; $r < 8; $r++) {
            for ($c = 0; $c < 8; $c++) {
                $p = $grid[$r][$c];
                if ($p !== null && $p[0] === $enemyColor) {
                    if ($this->isValidPieceMove($grid, $r, $c, $kr, $kc, $p)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function hasAnyLegalMoves(array $grid, string $color): bool
    {
        for ($fr = 0; $fr < 8; $fr++) {
            for ($fc = 0; $fc < 8; $fc++) {
                $piece = $grid[$fr][$fc];
                if ($piece === null || $piece[0] !== $color) {
                    continue;
                }

                for ($tr = 0; $tr < 8; $tr++) {
                    for ($tc = 0; $tc < 8; $tc++) {
                        if ($fr === $tr && $fc === $tc) {
                            continue;
                        }
                        $target = $grid[$tr][$tc];
                        if ($target !== null && $target[0] === $color) {
                            continue;
                        }
                        if ($this->isValidPieceMove($grid, $fr, $fc, $tr, $tc, $piece)) {
                            $sim = $grid;
                            $sim[$tr][$tc] = $piece;
                            $sim[$fr][$fc] = null;
                            if (!$this->isKingInCheck($sim, $color)) {
                                return true;
                            }
                        }
                    }
                }
            }
        }

        return false;
    }

    private function formatAlgebraicNotation(string $piece, int $fr, int $fc, int $tr, int $tc, bool $captured, bool $promoted): string
    {
        $files = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'];
        $fromSq = $files[$fc] . (8 - $fr);
        $toSq = $files[$tc] . (8 - $tr);
        $prefix = $piece[1] === 'P' ? ($captured ? $files[$fc] : '') : $piece[1];
        $cap = $captured ? 'x' : '';
        $promo = $promoted ? '=Q' : '';

        return "{$prefix}{$cap}{$toSq}{$promo} ({$fromSq}→{$toSq})";
    }

    private function getPieceName(string $code): string
    {
        return match ($code) {
            'P' => 'Pawn',
            'N' => 'Knight',
            'B' => 'Bishop',
            'R' => 'Rook',
            'Q' => 'Queen',
            'K' => 'King',
            default => 'Piece',
        };
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
