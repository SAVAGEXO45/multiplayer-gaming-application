<?php

namespace App\Enums;

enum RoomStatus: string
{
    case Waiting = 'waiting';
    case InProgress = 'in_progress';
    case Finished = 'finished';
    case Closed = 'closed';

    public function isJoinable(): bool
    {
        return $this === self::Waiting;
    }
}
