<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameMove extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_session_id',
        'user_id',
        'move_number',
        'move_data',
        'board_state_after',
    ];

    protected function casts(): array
    {
        return [
            'move_number' => 'integer',
            'move_data' => 'array',
            'board_state_after' => 'array',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(GameSession::class, 'game_session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
