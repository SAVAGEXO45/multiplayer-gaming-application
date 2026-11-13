<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_room_id',
        'game_id',
        'session_number',
        'status',
        'current_turn_user_id',
        'winner_user_id',
        'is_draw',
        'board_state',
        'scores',
        'result_reason',
        'moves_count',
        'started_at',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'session_number' => 'integer',
            'is_draw' => 'boolean',
            'board_state' => 'array',
            'scores' => 'array',
            'moves_count' => 'integer',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(GameRoom::class, 'game_room_id');
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function currentTurnUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'current_turn_user_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_user_id');
    }

    public function moves(): HasMany
    {
        return $this->hasMany(GameMove::class)->orderBy('move_number');
    }
}
