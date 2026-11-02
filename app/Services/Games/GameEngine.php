<?php

namespace App\Services\Games;

use App\Models\GameSession;
use App\Models\User;

interface GameEngine
{
    public function slug(): string;

    /**
     * @param  array<int, int>  $playerIds
     * @return array<string, mixed>
     */
    public function initialState(array $playerIds, string $difficulty): array;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function move(GameSession $session, User $user, array $payload): MoveResult;

    /**
     * @return array<string, mixed>
     */
    public function publicState(GameSession $session, ?User $viewer): array;

    public function performanceScore(GameSession $session, User $user): int;
}
