<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomPlayer extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_room_id',
        'user_id',
        'is_ready',
        'is_host',
        'score',
        'joined_at',
        'left_at',
    ];

    protected function casts(): array
    {
        return [
            'is_ready' => 'boolean',
            'is_host' => 'boolean',
            'score' => 'integer',
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(GameRoom::class, 'game_room_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
