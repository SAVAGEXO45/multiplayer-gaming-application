<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerStatistic extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'game_id',
        'games_played',
        'games_won',
        'games_lost',
        'games_drawn',
        'win_percentage',
        'total_points',
        'current_streak',
        'best_streak',
        'rank_position',
    ];

    protected function casts(): array
    {
        return [
            'games_played' => 'integer',
            'games_won' => 'integer',
            'games_lost' => 'integer',
            'games_drawn' => 'integer',
            'win_percentage' => 'float',
            'total_points' => 'integer',
            'current_streak' => 'integer',
            'best_streak' => 'integer',
            'rank_position' => 'integer',
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
