<?php

namespace App\Enums;

enum UserRole: string
{
    case Player = 'player';
    case Admin = 'admin';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
