<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Leaderboard extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'game_id',
        'period_type',
        'period_key',
        'points',
        'wins',
        'losses',
        'draws',
        'games_played',
        'win_percentage',
        'rank',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'wins' => 'integer',
            'losses' => 'integer',
            'draws' => 'integer',
            'games_played' => 'integer',
            'win_percentage' => 'float',
            'rank' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
