<?php

namespace App\Enums;

enum GameSessionStatus: string
{
    case Active = 'active';
    case Finished = 'finished';
    case Abandoned = 'abandoned';
}
