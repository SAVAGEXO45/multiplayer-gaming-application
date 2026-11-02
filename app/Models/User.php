<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $name
 * @property string $username
 * @property string $email
 * @property string|null $avatar_path
 * @property string|null $bio
 * @property UserRole $role
 * @property bool $is_suspended
 * @property Carbon|null $suspended_at
 * @property string|null $suspension_reason
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'username', 'email', 'password', 'avatar_path', 'bio', 'role', 'is_suspended', 'suspended_at', 'suspension_reason', 'last_seen_at'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => 'player',
        'is_suspended' => false,
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'role' => UserRole::class,
            'is_suspended' => 'boolean',
            'suspended_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (User $user): void {
            $user->statistics()->firstOrCreate();
        });
    }

    public function statistics(): HasOne
    {
        return $this->hasOne(PlayerStatistics::class);
    }

    public function gameSessions(): HasMany
    {
        return $this->hasMany(GameSession::class, 'created_by');
    }

    public function gameResults(): HasMany
    {
        return $this->hasMany(GameResult::class);
    }

    public function hostedRooms(): HasMany
    {
        return $this->hasMany(GameRoom::class, 'host_id');
    }

    public function roomPlayers(): HasMany
    {
        return $this->hasMany(RoomPlayer::class);
    }

    public function achievements(): HasMany
    {
        return $this->hasMany(UserAchievement::class);
    }

    public function rewardTransactions(): HasMany
    {
        return $this->hasMany(RewardTransaction::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isSuspended(): bool
    {
        return $this->is_suspended;
    }

    public function points(): int
    {
        return (int) ($this->statistics?->points ?? 0);
    }

    public function avatarUrl(): string
    {
        if ($this->avatar_path) {
            return asset('storage/'.$this->avatar_path);
        }

        return 'https://api.dicebear.com/9.x/bottts-neutral/svg?seed='.urlencode($this->username ?: $this->name);
    }

    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->map(fn (string $part): string => Str::substr($part, 0, 1))
            ->take(2)
            ->implode('');
    }
}
