<?php

namespace App\Models;

use App\Enums\RoomStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameRoom extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'game_id',
        'host_id',
        'max_players',
        'status',
        'is_private',
        'password',
        'current_session_id',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => RoomStatus::class,
            'is_private' => 'boolean',
            'max_players' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    protected $hidden = ['password'];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    public function players(): HasMany
    {
        return $this->hasMany(RoomPlayer::class);
    }

    public function activePlayers(): HasMany
    {
        return $this->players()->whereNull('left_at');
    }

    public function currentSession(): BelongsTo
    {
        return $this->belongsTo(GameSession::class, 'current_session_id');
    }

    public function hasPassword(): bool
    {
        return $this->password !== null;
    }

    public function isFull(): bool
    {
        return $this->activePlayers()->count() >= $this->max_players;
    }

    public function hasPlayer(User $user): bool
    {
        return $this->activePlayers()->where('user_id', $user->id)->exists();
    }
}
