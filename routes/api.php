<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GameController;
use App\Http\Controllers\Api\LeaderboardController;
use App\Http\Controllers\Api\PlayerController;
use App\Http\Controllers\Api\RoomController;
use Illuminate\Support\Facades\Route;

Route::post('register', [AuthController::class, 'register'])->middleware('throttle:auth');
Route::post('login', [AuthController::class, 'login'])->middleware('throttle:auth');

Route::middleware(['auth:sanctum', 'not_suspended', 'throttle:api'])->group(function () {
    Route::get('user', [AuthController::class, 'user']);
    Route::post('logout', [AuthController::class, 'logout']);

    Route::get('profile', [PlayerController::class, 'profile']);
    Route::get('statistics', [PlayerController::class, 'statistics']);
    Route::get('achievements', [PlayerController::class, 'achievements']);
    Route::get('rewards', [PlayerController::class, 'rewards']);
    Route::post('rewards/{reward:slug}/redeem', [PlayerController::class, 'redeemReward']);

    Route::get('games', [GameController::class, 'index']);
    Route::get('games/{game:slug}', [GameController::class, 'show']);
    Route::post('games/{game:slug}/start', [GameController::class, 'start']);
    Route::get('games/{game:slug}/history', [GameController::class, 'history']);

    Route::get('sessions/{session:uuid}', [GameController::class, 'session']);
    Route::post('games/{session:uuid}/move', [GameController::class, 'move'])
        ->middleware('throttle:game-moves');
    Route::post('sessions/{session:uuid}/resign', [GameController::class, 'resign']);

    Route::get('rooms', [RoomController::class, 'index']);
    Route::post('rooms', [RoomController::class, 'store']);
    Route::get('rooms/{room:code}', [RoomController::class, 'show']);
    Route::post('rooms/{room:code}/join', [RoomController::class, 'join']);
    Route::post('rooms/{room:code}/leave', [RoomController::class, 'leave']);
    Route::post('rooms/{room:code}/ready', [RoomController::class, 'ready']);
    Route::post('rooms/{room:code}/start', [RoomController::class, 'start']);

    Route::get('leaderboard', [LeaderboardController::class, 'index']);
    Route::get('leaderboard/weekly', [LeaderboardController::class, 'weekly']);
    Route::get('leaderboard/monthly', [LeaderboardController::class, 'monthly']);

    Route::prefix('admin')->middleware('admin')->group(function () {
        Route::get('dashboard', [AdminController::class, 'dashboard']);
        Route::get('users', [AdminController::class, 'users']);
        Route::get('games', [AdminController::class, 'games']);
        Route::get('rooms', [AdminController::class, 'rooms']);
        Route::get('activity', [AdminController::class, 'activity']);
        Route::patch('users/{user}/status', [AdminController::class, 'updateUserStatus']);
        Route::patch('users/{user}/role', [AdminController::class, 'updateUserRole']);
        Route::patch('games/{game}', [AdminController::class, 'updateGame']);
    });
});
