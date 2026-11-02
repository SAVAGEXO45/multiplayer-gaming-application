<?php

namespace App\Models;

use App\Enums\GameResultType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_session_id',
        'user_id',
        'game_id',
        'result',
        'score',
        'points_awarded',
        'is_multiplayer',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'result' => GameResultType::class,
            'score' => 'integer',
            'points_awarded' => 'integer',
            'is_multiplayer' => 'boolean',
            'metadata' => 'array',
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

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
