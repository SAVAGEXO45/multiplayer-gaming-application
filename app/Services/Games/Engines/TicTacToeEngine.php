<?php

namespace App\Services\Games\Engines;

use App\Models\GameSession;
use App\Models\User;
use App\Services\Games\GameEngine;
use App\Services\Games\MoveResult;
use Illuminate\Validation\ValidationException;

class TicTacToeEngine implements GameEngine
{
    private const WINNING_LINES = [
        [0, 1, 2], [3, 4, 5], [6, 7, 8],
        [0, 3, 6], [1, 4, 7], [2, 5, 8],
        [0, 4, 8], [2, 4, 6],
    ];

    public function slug(): string
    {
        return 'tic-tac-toe';
    }

    public function initialState(array $playerIds, string $difficulty): array
    {
        $marks = [];

        foreach (array_values($playerIds) as $index => $id) {
            $marks[(string) $id] = $index === 0 ? 'X' : 'O';
        }

        return [
            'players' => array_values($playerIds),
            'marks' => $marks,
            'board' => array_fill(0, 9, null),
            'move_count' => 0,
            'winning_line' => null,
            'last_move' => null,
        ];
    }

    public function move(GameSession $session, User $user, array $payload): MoveResult
    {
        $state = $session->state;
        $players = array_map('intval', $state['players']);

        if (! in_array($user->id, $players, true)) {
            throw ValidationException::withMessages(['game' => 'You are not a player in this game.']);
        }

        if (count($players) > 1 && (int) $session->current_turn_user_id !== $user->id) {
            throw ValidationException::withMessages(['game' => 'It is not your turn.']);
        }

        $position = filter_var($payload['position'] ?? null, FILTER_VALIDATE_INT);

        if ($position === false || $position === null || $position < 0 || $position > 8) {
            throw ValidationException::withMessages(['position' => 'Choose a valid square between 1 and 9.']);
        }

        if ($state['board'][$position] !== null) {
            throw ValidationException::withMessages(['position' => 'That square is already taken.']);
        }

        $state['board'][$position] = $state['marks'][(string) $user->id];
        $state['move_count']++;
        $state['last_move'] = ['user_id' => $user->id, 'position' => $position];

        $outcome = $this->evaluate($state['board']);
        $isSolo = count($players) === 1;

        if ($outcome['winner'] !== null) {
            $state['winning_line'] = $outcome['line'];

            return new MoveResult(
                $state,
                true,
                $outcome['winner'] === 'X' ? $players[0] : ($players[1] ?? null),
                false,
                'Game over.',
                null,
            );
        }

        if ($outcome['draw']) {
            return new MoveResult($state, true, null, true, "It's a draw!", null);
        }

        $nextTurn = $this->otherPlayer($players, $user->id);

        if ($isSolo) {
            $aiMove = $this->aiMove($state['board'], $session->difficulty);

            if ($aiMove !== null) {
                $state['board'][$aiMove] = 'O';
                $state['move_count']++;
                $state['last_move'] = ['user_id' => null, 'position' => $aiMove];

                $outcome = $this->evaluate($state['board']);

                if ($outcome['winner'] !== null) {
                    $state['winning_line'] = $outcome['line'];

                    return new MoveResult($state, true, null, false, 'The AI won this round.', null);
                }

                if ($outcome['draw']) {
                    return new MoveResult($state, true, null, true, "It's a draw!", null);
                }
            }

            $nextTurn = $players[0];
        }

        return new MoveResult($state, false, null, false, 'Move accepted.', $nextTurn);
    }

    public function publicState(GameSession $session, ?User $viewer): array
    {
        return $session->state;
    }

    public function performanceScore(GameSession $session, User $user): int
    {
        $state = $session->state;
        $moves = (int) ($state['move_count'] ?? 0);

        return $session->winner_id === $user->id ? max(1, 10 - $moves) : 0;
    }

    /**
     * @param  array<int, string|null>  $board
     * @return array{winner: string|null, draw: bool, line: array<int, int>|null}
     */
    private function evaluate(array $board): array
    {
        foreach (self::WINNING_LINES as $line) {
            [$a, $b, $c] = $line;

            if ($board[$a] !== null && $board[$a] === $board[$b] && $board[$a] === $board[$c]) {
                return ['winner' => $board[$a], 'draw' => false, 'line' => $line];
            }
        }

        return [
            'winner' => null,
            'draw' => ! in_array(null, $board, true),
            'line' => null,
        ];
    }

    /**
     * @param  array<int, int>  $players
     */
    private function otherPlayer(array $players, int $userId): ?int
    {
        foreach ($players as $playerId) {
            if ($playerId !== $userId) {
                return $playerId;
            }
        }

        return null;
    }

    /**
     * @param  array<int, string|null>  $board
     */
    private function aiMove(array $board, string $difficulty): ?int
    {
        $empty = array_keys(array_filter($board, fn ($cell) => $cell === null));

        if ($empty === []) {
            return null;
        }

        if ($difficulty === 'easy') {
            return $empty[array_rand($empty)];
        }

        if ($difficulty === 'medium' && random_int(1, 100) <= 35) {
            return $empty[array_rand($empty)];
        }

        if ($difficulty === 'hard') {
            return $this->minimaxChoice($board);
        }

        foreach ($empty as $position) {
            $copy = $board;
            $copy[$position] = 'O';

            if ($this->evaluate($copy)['winner'] === 'O') {
                return $position;
            }
        }

        foreach ($empty as $position) {
            $copy = $board;
            $copy[$position] = 'X';

            if ($this->evaluate($copy)['winner'] === 'X') {
                return $position;
            }
        }

        return in_array(4, $empty, true) ? 4 : $empty[array_rand($empty)];
    }

    /**
     * @param  array<int, string|null>  $board
     */
    private function minimaxChoice(array $board): int
    {
        $bestScore = -INF;
        $bestMove = array_key_first(array_filter($board, fn ($cell) => $cell === null));

        foreach ($board as $position => $cell) {
            if ($cell !== null) {
                continue;
            }

            $copy = $board;
            $copy[$position] = 'O';
            $score = $this->minimax($copy, false, 0);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMove = $position;
            }
        }

        return $bestMove;
    }

    /**
     * @param  array<int, string|null>  $board
     */
    private function minimax(array $board, bool $maximising, int $depth): int
    {
        $outcome = $this->evaluate($board);

        if ($outcome['winner'] === 'O') {
            return 10 - $depth;
        }

        if ($outcome['winner'] === 'X') {
            return $depth - 10;
        }

        if ($outcome['draw']) {
            return 0;
        }

        $scores = [];

        foreach ($board as $position => $cell) {
            if ($cell !== null) {
                continue;
            }

            $copy = $board;
            $copy[$position] = $maximising ? 'O' : 'X';
            $scores[] = $this->minimax($copy, ! $maximising, $depth + 1);
        }

        return $maximising ? max($scores) : min($scores);
    }
}
