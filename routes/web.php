<?php

use App\Http\Controllers\AchievementController;
use App\Http\Controllers\Admin\ActivityController as AdminActivityController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\GameController as AdminGameController;
use App\Http\Controllers\Admin\RewardController as AdminRewardController;
use App\Http\Controllers\Admin\RoomController as AdminRoomController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\GameSessionController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\PlayerProfileController;
use App\Http\Controllers\RewardController;
use App\Http\Controllers\RoomController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'index'])->name('home');

Route::middleware(['auth', 'verified', 'not_suspended'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('games', [GameController::class, 'index'])->name('games.index');
    Route::get('games/{game:slug}', [GameController::class, 'show'])->name('games.show');
    Route::post('games/{game:slug}/start', [GameController::class, 'start'])->name('games.start');

    Route::get('games/sessions/{session:uuid}', [GameSessionController::class, 'show'])->name('games.sessions.show');
    Route::post('games/sessions/{session:uuid}/move', [GameSessionController::class, 'move'])
        ->middleware('throttle:game-moves')
        ->name('games.sessions.move');
    Route::post('games/sessions/{session:uuid}/resign', [GameSessionController::class, 'resign'])
        ->name('games.sessions.resign');
    Route::get('games/sessions/{session:uuid}/state', [GameSessionController::class, 'state'])
        ->name('games.sessions.state');

    Route::get('rooms', [RoomController::class, 'index'])->name('rooms.index');
    Route::get('rooms/create', [RoomController::class, 'create'])->name('rooms.create');
    Route::post('rooms', [RoomController::class, 'store'])->name('rooms.store');
    Route::get('rooms/{room:code}', [RoomController::class, 'show'])->name('rooms.show');
    Route::post('rooms/{room:code}/join', [RoomController::class, 'join'])->name('rooms.join');
    Route::post('rooms/{room:code}/leave', [RoomController::class, 'leave'])->name('rooms.leave');
    Route::post('rooms/{room:code}/ready', [RoomController::class, 'ready'])->name('rooms.ready');
    Route::post('rooms/{room:code}/start', [RoomController::class, 'start'])->name('rooms.start');
    Route::get('rooms/{room:code}/state', [RoomController::class, 'state'])->name('rooms.state');

    Route::get('leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard');
    Route::get('players/{user:username}', [PlayerProfileController::class, 'show'])->name('players.show');
    Route::get('achievements', [AchievementController::class, 'index'])->name('achievements.index');
    Route::get('rewards', [RewardController::class, 'index'])->name('rewards.index');
    Route::post('rewards/{reward:slug}/redeem', [RewardController::class, 'redeem'])->name('rewards.redeem');

    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
        Route::patch('users/{user}/status', [AdminUserController::class, 'updateStatus'])->name('users.status');
        Route::patch('users/{user}/role', [AdminUserController::class, 'updateRole'])->name('users.role');
        Route::get('games', [AdminGameController::class, 'index'])->name('games.index');
        Route::patch('games/{game}', [AdminGameController::class, 'update'])->name('games.update');
        Route::get('rooms', [AdminRoomController::class, 'index'])->name('rooms.index');
        Route::patch('rooms/{room:code}/close', [AdminRoomController::class, 'close'])->name('rooms.close');
        Route::get('rewards', [AdminRewardController::class, 'index'])->name('rewards.index');
        Route::post('rewards', [AdminRewardController::class, 'store'])->name('rewards.store');
        Route::patch('rewards/{reward:slug}', [AdminRewardController::class, 'update'])->name('rewards.update');
        Route::get('activity', [AdminActivityController::class, 'index'])->name('activity.index');
    });
});

require __DIR__.'/settings.php';
