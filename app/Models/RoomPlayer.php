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
        'player_slot',
        'player_symbol',
        'status',
        'is_ready',
        'score',
        'joined_at',
        'last_ping_at',
    ];

    protected function casts(): array
    {
        return [
            'player_slot' => 'integer',
            'is_ready' => 'boolean',
            'score' => 'integer',
            'joined_at' => 'datetime',
            'last_ping_at' => 'datetime',
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
