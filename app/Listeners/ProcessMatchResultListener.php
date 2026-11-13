<?php

namespace App\Listeners;

use App\Events\MatchCompleted;
use App\Services\ScoringService;

class ProcessMatchResultListener
{
    public function __construct(
        private readonly ScoringService $scoringService
    ) {}

    public function handle(MatchCompleted $event): void
    {
        $this->scoringService->processCompletedSession($event->session);
    }
}
