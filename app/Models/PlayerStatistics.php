<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerStatistics extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'games_played',
        'wins',
        'losses',
        'draws',
        'points',
        'current_streak',
        'best_streak',
        'multiplayer_wins',
        'total_score',
        'last_game_at',
    ];

    protected function casts(): array
    {
        return [
            'games_played' => 'integer',
            'wins' => 'integer',
            'losses' => 'integer',
            'draws' => 'integer',
            'points' => 'integer',
            'current_streak' => 'integer',
            'best_streak' => 'integer',
            'multiplayer_wins' => 'integer',
            'total_score' => 'integer',
            'last_game_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function winRate(): float
    {
        return $this->games_played > 0
            ? round(($this->wins / $this->games_played) * 100, 1)
            : 0.0;
    }
}
