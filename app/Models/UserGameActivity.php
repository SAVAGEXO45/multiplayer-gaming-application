<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserGameActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'game_id',
        'is_favorite',
        'plays_count',
        'high_score',
        'max_level',
        'last_played_at',
    ];

    protected function casts(): array
    {
        return [
            'is_favorite' => 'boolean',
            'plays_count' => 'integer',
            'high_score' => 'integer',
            'max_level' => 'integer',
            'last_played_at' => 'datetime',
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
