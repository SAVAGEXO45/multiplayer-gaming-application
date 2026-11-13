<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Achievement extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'badge_color',
        'points_reward',
        'criteria_type',
        'criteria_value',
        'game_id',
    ];

    protected function casts(): array
    {
        return [
            'points_reward' => 'integer',
            'criteria_value' => 'integer',
        ];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'player_achievements')
            ->withPivot('unlocked_at')
            ->withTimestamps();
    }
}
