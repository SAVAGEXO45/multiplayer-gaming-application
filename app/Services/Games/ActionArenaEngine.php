<?php

namespace App\Services\Games;

use App\Models\GameSession;
use App\Services\Games\Contracts\GameEngineInterface;
use InvalidArgumentException;

class ActionArenaEngine implements GameEngineInterface
{
    public function __construct(
        private readonly string $gameSlug = 'cyber-strike-fps'
    ) {}

    public function initializeBoard(array $players): array
    {
        $playerRuns = [];
        foreach ($players as $p) {
            $uid = (int) $p['user_id'];
            $playerRuns[$uid] = [
                'user_id' => $uid,
                'slot' => (int) $p['slot'],
                'symbol' => (string) $p['symbol'],
                'score' => 0,
                'level' => 1,
                'combos' => 0,
                'finished' => false,
                'submissions' => 0,
                'last_event' => 'Ready in Arena',
            ];
        }

        return [
            'engine' => 'action_arena',
            'game_slug' => $this->gameSlug,
            'target_score' => 1500,
            'player_runs' => $playerRuns,
            'events' => [],
        ];
    }

    public function applyMove(GameSession $session, int $userId, array $moveData, array $players): array
    {
        $board = $session->board_state;
        if (!is_array($board) || !isset($board['player_runs'])) {
            $board = $this->initializeBoard($players);
        }

        $playerIds = array_map(fn ($p) => (int) $p['user_id'], $players);
        if (!in_array($userId, $playerIds, true)) {
            throw new InvalidArgumentException('You are not an active participant in this arena session.');
        }

        $scoreInput = isset($moveData['score']) ? (int) $moveData['score'] : null;
        $scoreDelta = isset($moveData['score_delta']) ? (int) $moveData['score_delta'] : null;

        if ($scoreInput === null && $scoreDelta === null) {
            throw new InvalidArgumentException('Move payload must include a valid score or score_delta.');
        }

        if ($scoreInput !== null && ($scoreInput < 0 || $scoreInput > 2000000)) {
            throw new InvalidArgumentException('Submitted score is out of valid arena bounds.');
        }

        $currentRun = $board['player_runs'][$userId] ?? [
            'user_id' => $userId,
            'slot' => 1,
            'symbol' => 'P1',
            'score' => 0,
            'level' => 1,
            'combos' => 0,
            'finished' => false,
            'submissions' => 0,
            'last_event' => 'Joined',
        ];

        $newScore = $scoreInput !== null
            ? max((int) $currentRun['score'], $scoreInput)
            : max(0, (int) $currentRun['score'] + $scoreDelta);

        $level = max((int) ($currentRun['level'] ?? 1), (int) ($moveData['level'] ?? 1));
        $combos = max((int) ($currentRun['combos'] ?? 0), (int) ($moveData['combos'] ?? 0));
        $finished = (bool) ($moveData['finished'] ?? false);
        $eventLabel = trim((string) ($moveData['event'] ?? ($finished ? 'Completed Arena Run' : 'Score Sync')));

        $currentRun['score'] = $newScore;
        $currentRun['level'] = min(99, $level);
        $currentRun['combos'] = min(999, $combos);
        $currentRun['finished'] = $currentRun['finished'] || $finished;
        $currentRun['submissions'] = ((int) $currentRun['submissions']) + 1;
        $currentRun['last_event'] = $eventLabel;

        $board['player_runs'][$userId] = $currentRun;

        $events = $board['events'] ?? [];
        $events[] = [
            'user_id' => $userId,
            'score' => $newScore,
            'level' => $currentRun['level'],
            'finished' => $currentRun['finished'],
            'event' => $eventLabel,
            'timestamp' => now()->toIso8601String(),
        ];
        $board['events'] = array_slice($events, -20);

        // Determine if all active players have finished their runs or someone hit an instant knockout target
        $allFinished = count($playerIds) >= 2;
        foreach ($playerIds as $pid) {
            $run = $board['player_runs'][$pid] ?? null;
            if (!$run || empty($run['finished'])) {
                $allFinished = false;
                break;
            }
        }

        $otherPlayerId = null;
        foreach ($playerIds as $pid) {
            if ($pid !== $userId) {
                $otherPlayerId = $pid;
                break;
            }
        }

        $status = 'in_progress';
        $winnerUserId = null;
        $isDraw = false;
        $resultReason = null;
        $nextTurnUserId = $otherPlayerId ?? $userId;

        if ($allFinished) {
            $status = 'completed';
            $nextTurnUserId = null;

            $highestScore = -1;
            $leaders = [];
            foreach ($playerIds as $pid) {
                $pScore = (int) ($board['player_runs'][$pid]['score'] ?? 0);
                if ($pScore > $highestScore) {
                    $highestScore = $pScore;
                    $leaders = [$pid];
                } elseif ($pScore === $highestScore) {
                    $leaders[] = $pid;
                }
            }

            if (count($leaders) === 1) {
                $winnerUserId = $leaders[0];
                $resultReason = "Arena High-Score Victory ({$highestScore} pts)";
            } else {
                $isDraw = true;
                $resultReason = "Arena Tie at {$highestScore} pts";
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
                'score' => $newScore,
                'level' => $currentRun['level'],
                'combos' => $currentRun['combos'],
                'finished' => $currentRun['finished'],
                'event' => $eventLabel,
            ],
        ];
    }
}
