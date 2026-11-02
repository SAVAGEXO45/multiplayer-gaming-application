<?php

namespace App\Enums;

enum RewardTransactionType: string
{
    case Earn = 'earn';
    case Spend = 'spend';
    case Adjustment = 'adjustment';
}
