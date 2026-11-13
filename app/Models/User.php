<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'avatar',
        'bio',
        'is_suspended',
        'is_online',
        'last_seen_at',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'password' => 'hashed',
            'is_suspended' => 'boolean',
            'is_online' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isPlayer(): bool
    {
        return $this->role === 'player';
    }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return str_starts_with($this->avatar, 'http')
                ? $this->avatar
                : asset('storage/' . $this->avatar);
        }

        $initials = urlencode($this->username ?: $this->name);
        return "https://ui-avatars.com/api/?name={$initials}&background=6366f1&color=ffffff&bold=true";
    }

    public function statistics(): HasMany
    {
        return $this->hasMany(PlayerStatistic::class);
    }

    public function globalStatistic(): HasOne
    {
        return $this->hasOne(PlayerStatistic::class)->whereNull('game_id');
    }

    public function hostedRooms(): HasMany
    {
        return $this->hasMany(GameRoom::class, 'host_user_id');
    }

    public function roomMemberships(): HasMany
    {
        return $this->hasMany(RoomPlayer::class);
    }

    public function wonSessions(): HasMany
    {
        return $this->hasMany(GameSession::class, 'winner_user_id');
    }

    public function gameMoves(): HasMany
    {
        return $this->hasMany(GameMove::class);
    }

    public function leaderboards(): HasMany
    {
        return $this->hasMany(Leaderboard::class);
    }

    public function achievements(): BelongsToMany
    {
        return $this->belongsToMany(Achievement::class, 'player_achievements')
            ->withPivot('unlocked_at')
            ->withTimestamps();
    }

    public function playerAchievements(): HasMany
    {
        return $this->hasMany(PlayerAchievement::class);
    }

    public function friendships(): HasMany
    {
        return $this->hasMany(Friendship::class, 'user_id');
    }

    public function friends(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'friendships', 'user_id', 'friend_id')
            ->withTimestamps();
    }

    public function sentFriendRequests(): HasMany
    {
        return $this->hasMany(FriendRequest::class, 'sender_id');
    }

    public function receivedFriendRequests(): HasMany
    {
        return $this->hasMany(FriendRequest::class, 'receiver_id');
    }

    public function platformNotifications(): HasMany
    {
        return $this->hasMany(PlatformNotification::class, 'user_id')->latest();
    }

    public function unreadPlatformNotifications(): HasMany
    {
        return $this->hasMany(PlatformNotification::class, 'user_id')->whereNull('read_at')->latest();
    }

    public function gameActivities(): HasMany
    {
        return $this->hasMany(UserGameActivity::class);
    }

    public function favoriteGames(): BelongsToMany
    {
        return $this->belongsToMany(Game::class, 'user_game_activities')
            ->wherePivot('is_favorite', true)
            ->withPivot(['is_favorite', 'plays_count', 'high_score', 'max_level', 'last_played_at'])
            ->withTimestamps();
    }

    public function recentlyPlayedGames(): BelongsToMany
    {
        return $this->belongsToMany(Game::class, 'user_game_activities')
            ->whereNotNull('user_game_activities.last_played_at')
            ->withPivot(['is_favorite', 'plays_count', 'high_score', 'max_level', 'last_played_at'])
            ->orderByDesc('user_game_activities.last_played_at');
    }
}
