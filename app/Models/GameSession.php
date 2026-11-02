<?php

namespace App\Models;

use App\Enums\GameSessionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'game_id',
        'room_id',
        'created_by',
        'status',
        'difficulty',
        'state',
        'current_turn_user_id',
        'winner_id',
        'is_draw',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => GameSessionStatus::class,
            'state' => 'array',
            'is_draw' => 'boolean',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (GameSession $session): void {
            $session->uuid ??= (string) \Illuminate\Support\Str::uuid();
        });
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(GameRoom::class, 'room_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function currentTurn(): BelongsTo
    {
        return $this->belongsTo(User::class, 'current_turn_user_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_id');
    }

    public function results(): HasMany
    {
        return $this->hasMany(GameResult::class);
    }

    public function isActive(): bool
    {
        return $this->status === GameSessionStatus::Active;
    }

    /**
     * @return array<int, int>
     */
    public function playerIds(): array
    {
        return collect($this->state['players'] ?? [])
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();
    }

    public function involves(User $user): bool
    {
        return in_array($user->id, $this->playerIds(), true);
    }
}
