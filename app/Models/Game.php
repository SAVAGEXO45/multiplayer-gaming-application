<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Game extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'tagline',
        'description',
        'instructions',
        'rules',
        'icon',
        'accent',
        'min_players',
        'max_players',
        'supports_multiplayer',
        'is_active',
        'points_win',
        'points_draw',
        'points_loss',
        'points_participation',
        'difficulties',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'supports_multiplayer' => 'boolean',
            'is_active' => 'boolean',
            'difficulties' => 'array',
            'min_players' => 'integer',
            'max_players' => 'integer',
            'points_win' => 'integer',
            'points_draw' => 'integer',
            'points_loss' => 'integer',
            'points_participation' => 'integer',
        ];
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(GameSession::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(GameResult::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(GameRoom::class);
    }

    public function difficultyOptions(): array
    {
        return $this->difficulties ?: ['easy', 'medium', 'hard'];
    }
}
