<?php

namespace App\Services\Games;

final class MoveResult
{
    /**
     * @param  array<string, mixed>  $state
     */
    public function __construct(
        public readonly array $state,
        public readonly bool $finished,
        public readonly ?int $winnerId,
        public readonly bool $isDraw,
        public readonly string $message,
        public readonly ?int $nextTurnUserId,
    ) {}
}
