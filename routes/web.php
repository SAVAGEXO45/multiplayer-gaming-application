<?php

use App\Http\Controllers\Web\AdminWebController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\FriendWebController;
use App\Http\Controllers\Web\GameWebController;
use App\Http\Controllers\Web\LandingController;
use App\Http\Controllers\Web\LeaderboardWebController;
use App\Http\Controllers\Web\NotificationWebController;
use App\Http\Controllers\Web\ProfileWebController;
use App\Http\Controllers\Web\RoomWebController;
use Illuminate\Support\Facades\Route;

// 1. Public Landing, Games Library, Game Rooms, Leaderboards & Tournaments
Route::get('/', [LandingController::class, 'index'])->name('landing');
Route::get('/games', [GameWebController::class, 'index'])->name('games.index');
Route::get('/games/{game:slug}', [GameWebController::class, 'show'])->name('games.show');
Route::get('/rooms', [RoomWebController::class, 'index'])->name('rooms.index');
Route::get('/leaderboards', [LeaderboardWebController::class, 'index'])->name('leaderboards.index');
Route::get('/tournaments', [LandingController::class, 'tournaments'])->name('tournaments.index');

// 2. Guest Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:auth-attempts');
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth-attempts');

    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

// 3. Authenticated Player Routes
Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/email/verify', [AuthController::class, 'verifyEmail'])->name('verification.verify');

    // Player Dashboard & Personal Game Library
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/library', [GameWebController::class, 'library'])->name('library.index');
    Route::post('/games/{game:slug}/score', [GameWebController::class, 'submitScore'])->name('games.score');
    Route::post('/games/{game:slug}/favorite', [GameWebController::class, 'toggleFavorite'])->name('games.favorite');

    // Multiplayer Game Rooms
    Route::post('/rooms', [RoomWebController::class, 'store'])->name('rooms.store');
    Route::post('/rooms/join-code', [RoomWebController::class, 'joinByCode'])->name('rooms.join-code');
    Route::get('/rooms/{room:room_code}', [RoomWebController::class, 'show'])->name('rooms.show');
    Route::get('/rooms/{room:room_code}/state', [RoomWebController::class, 'state'])->name('rooms.state');
    Route::post('/rooms/{room:room_code}/join', [RoomWebController::class, 'join'])->name('rooms.join');
    Route::post('/rooms/{room:room_code}/leave', [RoomWebController::class, 'leave'])->name('rooms.leave');
    Route::post('/rooms/{room:room_code}/start', [RoomWebController::class, 'startOrRestart'])->name('rooms.start');
    Route::post('/rooms/{room:room_code}/restart', [RoomWebController::class, 'startOrRestart'])->name('rooms.restart');
    Route::post('/rooms/{room:room_code}/moves', [RoomWebController::class, 'makeMove'])
        ->middleware('throttle:game-moves')
        ->name('rooms.moves');
    Route::post('/rooms/{room:room_code}/invite', [RoomWebController::class, 'invite'])->name('rooms.invite');

    // Friends & Social
    Route::get('/friends', [FriendWebController::class, 'index'])->name('friends.index');
    Route::post('/friends/requests', [FriendWebController::class, 'sendRequest'])->name('friends.request');
    Route::post('/friends/requests/{friendRequest}/accept', [FriendWebController::class, 'acceptRequest'])->name('friends.accept');
    Route::post('/friends/requests/{friendRequest}/reject', [FriendWebController::class, 'rejectRequest'])->name('friends.reject');
    Route::delete('/friends/{friend}', [FriendWebController::class, 'removeFriend'])->name('friends.remove');

    // Player Profile
    Route::get('/profile/{username?}', [ProfileWebController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfileWebController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileWebController::class, 'updatePassword'])->name('profile.password');

    // Notifications
    Route::get('/notifications', [NotificationWebController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationWebController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationWebController::class, 'markAllRead'])->name('notifications.read-all');

    // Reports
    Route::post('/reports', [AdminWebController::class, 'storeReport'])->name('reports.store');

    // 4. Admin Dashboard & Management Routes
    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
        Route::get('/', [AdminWebController::class, 'index'])->name('dashboard');
        Route::post('/users/{user}/toggle-suspension', [AdminWebController::class, 'toggleSuspension'])->name('users.toggle-suspension');
        Route::post('/users/{user}/role', [AdminWebController::class, 'updateRole'])->name('users.role');
        Route::post('/games/{game:slug}/toggle', [AdminWebController::class, 'toggleGame'])->name('games.toggle');
        Route::put('/games/{game:slug}/points', [AdminWebController::class, 'updateGamePoints'])->name('games.points');
        Route::post('/rooms/{room:room_code}/close', [AdminWebController::class, 'closeRoom'])->name('rooms.close');
        Route::post('/achievements', [AdminWebController::class, 'storeAchievement'])->name('achievements.store');
        Route::post('/reports/{report}/resolve', [AdminWebController::class, 'resolveReport'])->name('reports.resolve');
    });
});
