<?php

namespace App\Enums;

enum GameResultType: string
{
    case Win = 'win';
    case Loss = 'loss';
    case Draw = 'draw';
}
