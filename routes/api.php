<?php

use App\Http\Controllers\Api\AchievementApiController;
use App\Http\Controllers\Api\AdminApiController;
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\FriendApiController;
use App\Http\Controllers\Api\GameApiController;
use App\Http\Controllers\Api\GameMoveApiController;
use App\Http\Controllers\Api\GameRoomApiController;
use App\Http\Controllers\Api\LeaderboardApiController;
use App\Http\Controllers\Api\NotificationApiController;
use App\Http\Controllers\Api\UserApiController;
use Illuminate\Support\Facades\Route;

// Public Authentication Routes
Route::prefix('auth')->middleware('throttle:auth-attempts')->group(function () {
    Route::post('/register', [AuthApiController::class, 'register']);
    Route::post('/login', [AuthApiController::class, 'login']);
});

// Public Game Catalog & Leaderboards
Route::get('/games', [GameApiController::class, 'index']);
Route::get('/games/{game:slug}', [GameApiController::class, 'show']);
Route::get('/leaderboards', [LeaderboardApiController::class, 'index']);
Route::get('/achievements', [AchievementApiController::class, 'index']);

// Protected API Routes (Sanctum + Active Session Auth Support)
Route::middleware(['auth:sanctum', 'active'])->group(function () {
    // Auth
    Route::get('/auth/me', [AuthApiController::class, 'me']);
    Route::post('/auth/logout', [AuthApiController::class, 'logout']);

    // Users & Profiles
    Route::get('/users', [UserApiController::class, 'index']);
    Route::get('/users/{username}', [UserApiController::class, 'show']);
    Route::put('/profile', [UserApiController::class, 'updateProfile']);

    // Game Rooms
    Route::get('/rooms', [GameRoomApiController::class, 'index']);
    Route::post('/rooms', [GameRoomApiController::class, 'store']);
    Route::post('/rooms/join-code', [GameRoomApiController::class, 'joinByCode']);
    Route::get('/rooms/{room:room_code}', [GameRoomApiController::class, 'show']);
    Route::post('/rooms/{room:room_code}/join', [GameRoomApiController::class, 'join']);
    Route::post('/rooms/{room:room_code}/leave', [GameRoomApiController::class, 'leave']);
    Route::post('/rooms/{room:room_code}/start', [GameRoomApiController::class, 'startOrRestart']);
    Route::post('/rooms/{room:room_code}/restart', [GameRoomApiController::class, 'startOrRestart']);
    Route::post('/rooms/{room:room_code}/invite', [GameRoomApiController::class, 'invite']);

    // Game Moves & Sessions
    Route::post('/rooms/{room:room_code}/moves', [GameMoveApiController::class, 'store'])
        ->middleware('throttle:game-moves');
    Route::get('/sessions/{session}', [GameMoveApiController::class, 'showSession']);

    // Friends & Social
    Route::get('/friends', [FriendApiController::class, 'index']);
    Route::post('/friends/requests', [FriendApiController::class, 'sendRequest']);
    Route::post('/friends/requests/{friendRequest}/accept', [FriendApiController::class, 'acceptRequest']);
    Route::post('/friends/requests/{friendRequest}/reject', [FriendApiController::class, 'rejectRequest']);
    Route::delete('/friends/{friend}', [FriendApiController::class, 'removeFriend']);

    // Notifications
    Route::get('/notifications', [NotificationApiController::class, 'index']);
    Route::post('/notifications/{notification}/read', [NotificationApiController::class, 'markRead']);
    Route::post('/notifications/read-all', [NotificationApiController::class, 'markAllRead']);

    // Admin Management Routes
    Route::prefix('admin')->middleware('admin')->group(function () {
        Route::get('/overview', [AdminApiController::class, 'overview']);
        Route::get('/users', [AdminApiController::class, 'users']);
        Route::post('/users/{user}/toggle-suspension', [AdminApiController::class, 'toggleUserSuspension']);
        Route::post('/games/{game:slug}/toggle-status', [AdminApiController::class, 'toggleGameStatus']);
        Route::get('/rooms', [AdminApiController::class, 'activeRooms']);
        Route::post('/achievements', [AdminApiController::class, 'createAchievement']);
    });
});
