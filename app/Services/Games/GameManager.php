<?php

namespace App\Services\Games;

use App\Models\Game;
use App\Services\Games\Engines\NumberGuessEngine;
use App\Services\Games\Engines\RockPaperScissorsEngine;
use App\Services\Games\Engines\TicTacToeEngine;
use InvalidArgumentException;

class GameManager
{
    /**
     * @var array<string, GameEngine>
     */
    private array $engines = [];

    public function __construct(
        TicTacToeEngine $ticTacToe,
        RockPaperScissorsEngine $rockPaperScissors,
        NumberGuessEngine $numberGuess,
    ) {
        foreach ([$ticTacToe, $rockPaperScissors, $numberGuess] as $engine) {
            $this->engines[$engine->slug()] = $engine;
        }
    }

    public function for(Game $game): GameEngine
    {
        return $this->forSlug($game->slug);
    }

    public function forSlug(string $slug): GameEngine
    {
        if (! isset($this->engines[$slug])) {
            throw new InvalidArgumentException("No engine registered for game [{$slug}].");
        }

        return $this->engines[$slug];
    }

    /**
     * @return array<int, string>
     */
    public function slugs(): array
    {
        return array_keys($this->engines);
    }
}
