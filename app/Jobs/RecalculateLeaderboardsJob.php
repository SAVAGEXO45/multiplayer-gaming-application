<?php

namespace App\Jobs;

use App\Services\ScoringService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RecalculateLeaderboardsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ?int $gameId = null
    ) {}

    public function handle(ScoringService $scoringService): void
    {
        $scoringService->recalculateRankings($this->gameId);
    }
}
