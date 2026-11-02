<?php

namespace App\Services\Games\Engines;

use App\Models\GameSession;
use App\Models\User;
use App\Services\Games\GameEngine;
use App\Services\Games\MoveResult;
use Illuminate\Validation\ValidationException;

class NumberGuessEngine implements GameEngine
{
    public function slug(): string
    {
        return 'number-guess';
    }

    public function initialState(array $playerIds, string $difficulty): array
    {
        $config = match ($difficulty) {
            'easy' => ['min' => 1, 'max' => 50, 'attempts' => 10],
            'hard' => ['min' => 1, 'max' => 100, 'attempts' => 5],
            default => ['min' => 1, 'max' => 100, 'attempts' => 7],
        };

        return [
            'players' => array_values($playerIds),
            'min' => $config['min'],
            'max' => $config['max'],
            'secret' => random_int($config['min'], $config['max']),
            'max_attempts' => $config['attempts'],
            'attempts' => [],
            'won' => false,
        ];
    }

    public function move(GameSession $session, User $user, array $payload): MoveResult
    {
        $state = $session->state;
        $players = array_map('intval', $state['players']);

        if (! in_array($user->id, $players, true)) {
            throw ValidationException::withMessages(['game' => 'You are not a player in this game.']);
        }

        if (count($players) > 1) {
            throw ValidationException::withMessages(['game' => 'Number Guess is a solo challenge.']);
        }

        $guess = filter_var($payload['guess'] ?? null, FILTER_VALIDATE_INT);

        if ($guess === false || $guess === null || $guess < $state['min'] || $guess > $state['max']) {
            throw ValidationException::withMessages([
                'guess' => "Enter a number between {$state['min']} and {$state['max']}.",
            ]);
        }

        $secret = (int) $state['secret'];
        $remaining = $state['max_attempts'] - count($state['attempts']);
        $feedback = match (true) {
            $guess < $secret => 'higher',
            $guess > $secret => 'lower',
            default => 'correct',
        };

        $state['attempts'][] = [
            'guess' => $guess,
            'feedback' => $feedback,
            'remaining' => max(0, $remaining - 1),
        ];

        if ($feedback === 'correct') {
            $state['won'] = true;

            return new MoveResult($state, true, $user->id, false, "Correct! The number was {$secret}.", null);
        }

        if (count($state['attempts']) >= $state['max_attempts']) {
            return new MoveResult($state, true, null, false, "Out of attempts. The number was {$secret}.", null);
        }

        return new MoveResult(
            $state,
            false,
            null,
            false,
            $feedback === 'higher' ? 'Too low — go higher.' : 'Too high — go lower.',
            $user->id,
        );
    }

    public function publicState(GameSession $session, ?User $viewer): array
    {
        $state = $session->state;
        unset($state['secret']);

        $state['attempts_used'] = count($state['attempts']);
        $state['attempts_left'] = max(0, $state['max_attempts'] - count($state['attempts']));
        $state['secret_number'] = $session->isActive() ? null : ($session->state['secret'] ?? null);

        return $state;
    }

    public function performanceScore(GameSession $session, User $user): int
    {
        if ($session->winner_id !== $user->id) {
            return 0;
        }

        $attempts = count($session->state['attempts'] ?? []);
        $max = (int) ($session->state['max_attempts'] ?? 7);

        return max(1, ($max - $attempts + 1) * 10);
    }
}
