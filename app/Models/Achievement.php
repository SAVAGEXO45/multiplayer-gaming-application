<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Achievement extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'icon',
        'tier',
        'criteria_type',
        'criteria_value',
        'points_reward',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'criteria_value' => 'integer',
            'points_reward' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function unlocks(): HasMany
    {
        return $this->hasMany(UserAchievement::class);
    }
}
