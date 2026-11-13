<?php

namespace App\Services\Games;

use App\Models\GameSession;
use App\Services\Games\Contracts\GameEngineInterface;
use InvalidArgumentException;

class RockPaperScissorsEngine implements GameEngineInterface
{
    private const VALID_CHOICES = ['rock', 'paper', 'scissors'];
    private const TARGET_SCORE = 3; // Best of 5 (first to 3 round wins)
    private const MAX_ROUNDS = 7;   // Cap if too many ties

    public function initializeBoard(array $players): array
    {
        $scores = [];
        foreach ($players as $player) {
            $scores[$player['user_id']] = 0;
        }

        return [
            'type' => 'rock-paper-scissors',
            'current_round' => 1,
            'target_score' => self::TARGET_SCORE,
            'max_rounds' => self::MAX_ROUNDS,
            'scores' => $scores,
            'current_choices' => [], // [user_id => 'rock'|'paper'|'scissors']
            'locked_players' => [],
            'rounds_history' => [],
        ];
    }

    public function applyMove(GameSession $session, int $userId, array $moveData, array $players): array
    {
        if ($session->status !== 'in_progress') {
            throw new InvalidArgumentException('This game session has already ended.');
        }

        $choice = isset($moveData['choice']) ? strtolower(trim((string) $moveData['choice'])) : '';
        if (!in_array($choice, self::VALID_CHOICES, true)) {
            throw new InvalidArgumentException('Invalid choice. Must be rock, paper, or scissors.');
        }

        $board = $session->board_state;
        $currentChoices = $board['current_choices'] ?? [];

        if (isset($currentChoices[$userId])) {
            throw new InvalidArgumentException('You have already locked in your choice for this round.');
        }

        $currentChoices[$userId] = $choice;
        $board['current_choices'] = $currentChoices;
        $board['locked_players'] = array_map('intval', array_keys($currentChoices));

        $playerIds = array_map(fn ($p) => (int) $p['user_id'], $players);
        $status = 'in_progress';
        $winnerUserId = null;
        $isDraw = false;
        $resultReason = null;
        $nextTurnUserId = null;

        // Determine if only one player has locked in so far
        if (count($currentChoices) < 2) {
            foreach ($playerIds as $pid) {
                if (!isset($currentChoices[$pid])) {
                    $nextTurnUserId = $pid;
                    break;
                }
            }
        } else {
            // Both players have locked in! Resolve the round.
            $p1Id = $playerIds[0];
            $p2Id = $playerIds[1] ?? $playerIds[0];
            $c1 = $currentChoices[$p1Id];
            $c2 = $currentChoices[$p2Id];

            $roundWinnerId = $this->resolveRoundWinner($p1Id, $c1, $p2Id, $c2);
            if ($roundWinnerId !== null) {
                $board['scores'][$roundWinnerId] = ($board['scores'][$roundWinnerId] ?? 0) + 1;
            }

            $board['rounds_history'][] = [
                'round' => $board['current_round'],
                'choices' => [
                    $p1Id => $c1,
                    $p2Id => $c2,
                ],
                'winner_user_id' => $roundWinnerId,
            ];

            // Reset current round choices
            $board['current_choices'] = [];
            $board['locked_players'] = [];

            // Check match completion conditions
            $p1Score = $board['scores'][$p1Id] ?? 0;
            $p2Score = $board['scores'][$p2Id] ?? 0;
            $target = $board['target_score'] ?? self::TARGET_SCORE;
            $maxRounds = $board['max_rounds'] ?? self::MAX_ROUNDS;

            if ($p1Score >= $target) {
                $status = 'completed';
                $winnerUserId = $p1Id;
                $resultReason = "Won Best-of-5 series ({$p1Score} - {$p2Score})";
            } elseif ($p2Score >= $target) {
                $status = 'completed';
                $winnerUserId = $p2Id;
                $resultReason = "Won Best-of-5 series ({$p2Score} - {$p1Score})";
            } elseif ($board['current_round'] >= $maxRounds) {
                $status = 'completed';
                if ($p1Score > $p2Score) {
                    $winnerUserId = $p1Id;
                    $resultReason = "Series ended after {$maxRounds} rounds ({$p1Score} - {$p2Score})";
                } elseif ($p2Score > $p1Score) {
                    $winnerUserId = $p2Id;
                    $resultReason = "Series ended after {$maxRounds} rounds ({$p2Score} - {$p1Score})";
                } else {
                    $isDraw = true;
                    $resultReason = "Series tied after {$maxRounds} rounds ({$p1Score} - {$p2Score})";
                }
            } else {
                $board['current_round']++;
                $nextTurnUserId = $p1Id;
            }
        }

        return [
            'board_state' => $board,
            'next_turn_user_id' => $nextTurnUserId,
            'status' => $status,
            'winner_user_id' => $winnerUserId,
            'is_draw' => $isDraw,
            'result_reason' => $resultReason,
            'sanitized_move' => [
                'round' => $board['current_round'],
                'locked' => true,
            ],
        ];
    }

    private function resolveRoundWinner(int $p1Id, string $c1, int $p2Id, string $c2): ?int
    {
        if ($c1 === $c2) {
            return null;
        }

        $beats = [
            'rock' => 'scissors',
            'paper' => 'rock',
            'scissors' => 'paper',
        ];

        return $beats[$c1] === $c2 ? $p1Id : $p2Id;
    }
}
