<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GameRoom extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_code',
        'name',
        'game_id',
        'host_user_id',
        'visibility',
        'status',
        'max_players',
        'settings',
        'started_at',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'max_players' => 'integer',
            'settings' => 'array',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'room_code';
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_user_id');
    }

    public function roomPlayers(): HasMany
    {
        return $this->hasMany(RoomPlayer::class)->orderBy('player_slot');
    }

    public function activePlayers(): HasMany
    {
        return $this->hasMany(RoomPlayer::class)
            ->whereIn('status', ['active', 'ready'])
            ->orderBy('player_slot');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(GameSession::class)->orderByDesc('session_number');
    }

    public function activeSession(): HasOne
    {
        return $this->hasOne(GameSession::class)->latestOfMany('id');
    }

    public function hasPlayer(int $userId): bool
    {
        return $this->roomPlayers()
            ->where('user_id', $userId)
            ->whereIn('status', ['active', 'ready', 'disconnected'])
            ->exists();
    }

    public function isHost(int $userId): bool
    {
        return (int) $this->host_user_id === $userId;
    }

    public function isFull(): bool
    {
        return $this->activePlayers()->count() >= $this->max_players;
    }
}
