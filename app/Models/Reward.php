<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reward extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'type',
        'icon',
        'cost',
        'payload',
        'stock',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'cost' => 'integer',
            'stock' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(RewardTransaction::class);
    }

    public function isAvailable(): bool
    {
        return $this->is_active && ($this->stock === null || $this->stock > 0);
    }
}
