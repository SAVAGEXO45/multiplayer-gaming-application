<?php

namespace App\Services\Games\Engines;

use App\Models\GameSession;
use App\Models\User;
use App\Services\Games\GameEngine;
use App\Services\Games\MoveResult;
use Illuminate\Validation\ValidationException;

class RockPaperScissorsEngine implements GameEngine
{
    private const CHOICES = ['rock', 'paper', 'scissors'];

    public function slug(): string
    {
        return 'rock-paper-scissors';
    }

    public function initialState(array $playerIds, string $difficulty): array
    {
        return [
            'players' => array_values($playerIds),
            'round' => 1,
            'round_limit' => 5,
            'wins_needed' => 3,
            'scores' => collect($playerIds)->mapWithKeys(fn ($id) => [(string) $id => 0])->all(),
            'ai_score' => 0,
            'choices' => [],
            'history' => [],
        ];
    }

    public function move(GameSession $session, User $user, array $payload): MoveResult
    {
        $state = $session->state;
        $players = array_map('intval', $state['players']);
        $choice = $payload['choice'] ?? null;

        if (! in_array($user->id, $players, true)) {
            throw ValidationException::withMessages(['game' => 'You are not a player in this game.']);
        }

        if (! is_string($choice) || ! in_array($choice, self::CHOICES, true)) {
            throw ValidationException::withMessages(['choice' => 'Choose rock, paper or scissors.']);
        }

        if (array_key_exists((string) $user->id, $state['choices'])) {
            throw ValidationException::withMessages(['choice' => 'You already locked in a choice this round.']);
        }

        $state['choices'][(string) $user->id] = $choice;
        $solo = count($players) === 1;
        $aiChoice = null;

        if ($solo) {
            $aiChoice = $this->aiChoice($state, $session->difficulty);
        } else {
            $pending = array_values(array_filter(
                $players,
                fn (int $id): bool => ! array_key_exists((string) $id, $state['choices']),
            ));

            if ($pending !== []) {
                return new MoveResult($state, false, null, false, 'Choice locked. Waiting for your opponent.', $pending[0]);
            }
        }

        $resolved = $this->resolveRound($state, $players, $aiChoice, $solo);

        return new MoveResult(
            $resolved['state'],
            $resolved['finished'],
            $resolved['winner_id'],
            $resolved['finished'] && $resolved['winner_id'] === null,
            $resolved['message'],
            $resolved['finished'] ? null : $players[0],
        );
    }

    public function publicState(GameSession $session, ?User $viewer): array
    {
        $state = $session->state;
        $players = array_map('intval', $state['players']);

        if (count($players) > 1 && $viewer !== null && $session->isActive()) {
            $state['choices'] = array_filter(
                $state['choices'],
                fn ($value, $key): bool => (int) $key === $viewer->id,
                ARRAY_FILTER_USE_BOTH,
            );
        }

        return $state;
    }

    public function performanceScore(GameSession $session, User $user): int
    {
        return $session->winner_id === $user->id ? 10 : 0;
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function aiChoice(array $state, string $difficulty): string
    {
        $history = $state['history'] ?? [];
        $playerId = (string) ($state['players'][0] ?? '');

        $playerChoices = collect($history)
            ->map(fn (array $round): ?string => $round['choices'][$playerId] ?? null)
            ->filter()
            ->values();

        if ($difficulty === 'easy') {
            return self::CHOICES[array_rand(self::CHOICES)];
        }

        if ($difficulty === 'hard' && $playerChoices->isNotEmpty()) {
            $mostCommon = $playerChoices->countBy()->sortDesc()->keys()->first();

            return $this->beats($mostCommon);
        }

        if ($difficulty === 'medium' && $playerChoices->isNotEmpty() && random_int(1, 100) <= 50) {
            return $this->beats($playerChoices->last());
        }

        return self::CHOICES[array_rand(self::CHOICES)];
    }

    private function beats(string $choice): string
    {
        return match ($choice) {
            'rock' => 'paper',
            'paper' => 'scissors',
            default => 'rock',
        };
    }

    /**
     * @param  array<string, mixed>  $state
     * @param  array<int, int>  $players
     * @return array{state: array<string, mixed>, finished: bool, winner_id: int|null, message: string}
     */
    private function resolveRound(array $state, array $players, ?string $aiChoice, bool $solo): array
    {
        $choices = $state['choices'];
        $scores = $state['scores'];
        $playerOne = $players[0];
        $playerTwo = $solo ? null : ($players[1] ?? null);

        $first = $choices[(string) $playerOne] ?? null;
        $second = $solo ? $aiChoice : ($playerTwo !== null ? ($choices[(string) $playerTwo] ?? null) : null);
        $roundWinner = $this->compare($first, $second);

        if ($roundWinner === 1) {
            $scores[(string) $playerOne] = ($scores[(string) $playerOne] ?? 0) + 1;
        } elseif ($roundWinner === 2 && $playerTwo !== null) {
            $scores[(string) $playerTwo] = ($scores[(string) $playerTwo] ?? 0) + 1;
        } elseif ($roundWinner === 2 && $solo) {
            $state['ai_score'] = ($state['ai_score'] ?? 0) + 1;
        }

        $state['scores'] = $scores;
        $state['history'][] = [
            'round' => $state['round'],
            'choices' => $solo ? [(string) $playerOne => $first] : $choices,
            'ai_choice' => $solo ? $second : null,
            'winner_id' => match ($roundWinner) {
                1 => $playerOne,
                2 => $playerTwo,
                default => null,
            },
        ];

        $state['round']++;
        $state['choices'] = [];

        $playerScore = (int) ($scores[(string) $playerOne] ?? 0);
        $opponentScore = $solo ? (int) ($state['ai_score'] ?? 0) : (int) ($scores[(string) $playerTwo] ?? 0);
        $finished = $playerScore >= $state['wins_needed']
            || $opponentScore >= $state['wins_needed']
            || $state['round'] > $state['round_limit'];

        $message = match ($roundWinner) {
            null => 'Round drawn.',
            1 => 'You won the round.',
            default => $solo ? 'The AI won the round.' : 'Opponent won the round.',
        };

        if (! $finished) {
            return [
                'state' => $state,
                'finished' => false,
                'winner_id' => null,
                'message' => $message.' Round '.$state['round'].'.',
            ];
        }

        $winnerId = match (true) {
            $playerScore > $opponentScore => $playerOne,
            $playerScore === $opponentScore => null,
            default => $playerTwo,
        };

        return [
            'state' => $state,
            'finished' => true,
            'winner_id' => $winnerId,
            'message' => $winnerId === null ? 'Match drawn.' : 'Match complete.',
        ];
    }

    private function compare(?string $first, ?string $second): int
    {
        if ($first === null || $second === null || $first === $second) {
            return 0;
        }

        $wins = ['rock' => 'scissors', 'paper' => 'rock', 'scissors' => 'paper'];

        return $wins[$first] === $second ? 1 : 2;
    }
}
