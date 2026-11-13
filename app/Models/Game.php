<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Game extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'category',
        'description',
        'instructions',
        'rules',
        'controls_summary',
        'min_players',
        'max_players',
        'icon',
        'cover_gradient',
        'thumbnail',
        'is_active',
        'is_featured',
        'is_trending',
        'is_new_release',
        'rating',
        'plays_count',
        'points_per_win',
        'points_per_draw',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_trending' => 'boolean',
            'is_new_release' => 'boolean',
            'rating' => 'float',
            'plays_count' => 'integer',
            'min_players' => 'integer',
            'max_players' => 'integer',
            'points_per_win' => 'integer',
            'points_per_draw' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(GameRoom::class);
    }

    public function activeRooms(): HasMany
    {
        return $this->hasMany(GameRoom::class)->whereIn('status', ['waiting', 'in_progress']);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(GameSession::class);
    }

    public function statistics(): HasMany
    {
        return $this->hasMany(PlayerStatistic::class);
    }

    public function leaderboards(): HasMany
    {
        return $this->hasMany(Leaderboard::class);
    }

    public function achievements(): HasMany
    {
        return $this->hasMany(Achievement::class);
    }

    public function userActivities(): HasMany
    {
        return $this->hasMany(UserGameActivity::class);
    }
}
