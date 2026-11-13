<?php

namespace App\Services\Games;

use App\Services\Games\Contracts\GameEngineInterface;
use InvalidArgumentException;

class GameEngineFactory
{
    public const ACTION_GAME_SLUGS = [
        'cyber-strike-fps',
        'nitro-apex-gt',
        'shadow-dojo-brawl',
        'emberfall-survivors',
        'deadzone-outbreak',
        'street-hoops-pro',
        'moto-xtreme-trials',
        'nova-vanguard-3d',
        'arena-tag-royale',
        'prism-breaker-overdrive',
    ];

    public static function make(string $gameSlug): GameEngineInterface
    {
        if (in_array($gameSlug, self::ACTION_GAME_SLUGS, true)) {
            return new ActionArenaEngine($gameSlug);
        }

        throw new InvalidArgumentException("Unsupported game engine: {$gameSlug}");
    }
}
