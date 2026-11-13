<?php

namespace App\Events;

use App\Models\GameSession;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MatchCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public GameSession $session
    ) {}
}
