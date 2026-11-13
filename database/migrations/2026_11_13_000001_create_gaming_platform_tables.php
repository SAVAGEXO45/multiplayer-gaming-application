<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Games Table
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug', 60)->unique();
            $table->string('category', 50)->default('Strategy');
            $table->text('description');
            $table->text('instructions');
            $table->text('rules');
            $table->unsignedTinyInteger('min_players')->default(2);
            $table->unsignedTinyInteger('max_players')->default(2);
            $table->string('icon', 50)->default('gamepad');
            $table->string('cover_gradient')->default('from-indigo-600 via-purple-600 to-cyan-500');
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('points_per_win')->default(100);
            $table->unsignedInteger('points_per_draw')->default(25);
            $table->timestamps();
        });

        // 2. Game Rooms Table
        Schema::create('game_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('room_code', 12)->unique();
            $table->string('name', 100);
            $table->foreignId('game_id')->constrained('games')->cascadeOnDelete();
            $table->foreignId('host_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('visibility', 20)->default('public')->index(); // 'public' or 'private'
            $table->string('status', 20)->default('waiting')->index(); // 'waiting', 'in_progress', 'completed', 'cancelled'
            $table->unsignedTinyInteger('max_players')->default(2);
            $table->json('settings')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['game_id', 'status', 'visibility']);
        });

        // 3. Room Players Table
        Schema::create('room_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_room_id')->constrained('game_rooms')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('player_slot')->default(1);
            $table->string('player_symbol', 20)->nullable(); // 'X', 'O', 'R', 'Y', 'w', 'b', 'P1', 'P2'
            $table->string('status', 20)->default('active')->index(); // 'active', 'ready', 'disconnected', 'left'
            $table->boolean('is_ready')->default(true);
            $table->unsignedInteger('score')->default(0);
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamp('last_ping_at')->nullable();
            $table->timestamps();

            $table->unique(['game_room_id', 'user_id']);
            $table->index(['game_room_id', 'status']);
        });

        // 4. Game Sessions Table
        Schema::create('game_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_room_id')->constrained('game_rooms')->cascadeOnDelete();
            $table->foreignId('game_id')->constrained('games')->cascadeOnDelete();
            $table->unsignedSmallInteger('session_number')->default(1);
            $table->string('status', 20)->default('in_progress')->index(); // 'in_progress', 'completed', 'abandoned'
            $table->foreignId('current_turn_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('winner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_draw')->default(false);
            $table->json('board_state');
            $table->json('scores')->nullable();
            $table->string('result_reason', 100)->nullable();
            $table->unsignedInteger('moves_count')->default(0);
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['game_room_id', 'status']);
            $table->index(['game_id', 'status']);
        });

        // 5. Game Moves Table
        Schema::create('game_moves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_session_id')->constrained('game_sessions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('move_number');
            $table->json('move_data');
            $table->json('board_state_after')->nullable();
            $table->timestamps();

            $table->index(['game_session_id', 'move_number']);
        });

        // 6. Player Statistics Table
        Schema::create('player_statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('game_id')->nullable()->constrained('games')->cascadeOnDelete(); // null = global aggregate
            $table->unsignedInteger('games_played')->default(0);
            $table->unsignedInteger('games_won')->default(0);
            $table->unsignedInteger('games_lost')->default(0);
            $table->unsignedInteger('games_drawn')->default(0);
            $table->decimal('win_percentage', 5, 2)->default(0.00);
            $table->unsignedInteger('total_points')->default(0)->index();
            $table->unsignedInteger('current_streak')->default(0);
            $table->unsignedInteger('best_streak')->default(0);
            $table->unsignedInteger('rank_position')->nullable()->index();
            $table->timestamps();

            $table->unique(['user_id', 'game_id']);
            $table->index(['game_id', 'total_points', 'games_won']);
        });

        // 7. Leaderboards Table (Global, Weekly, Monthly, Game-Specific)
        Schema::create('leaderboards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('game_id')->nullable()->constrained('games')->cascadeOnDelete();
            $table->string('period_type', 20)->index(); // 'global', 'weekly', 'monthly'
            $table->string('period_key', 20)->index();  // 'all', '2026-W46', '2026-11'
            $table->unsignedInteger('points')->default(0)->index();
            $table->unsignedInteger('wins')->default(0);
            $table->unsignedInteger('losses')->default(0);
            $table->unsignedInteger('draws')->default(0);
            $table->unsignedInteger('games_played')->default(0);
            $table->decimal('win_percentage', 5, 2)->default(0.00);
            $table->unsignedInteger('rank')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'game_id', 'period_type', 'period_key'], 'leaderboards_user_game_period_unique');
            $table->index(['period_type', 'period_key', 'game_id', 'points'], 'leaderboards_ranking_idx');
        });

        // 8. Friendships Table
        Schema::create('friendships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('friend_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'friend_id']);
            $table->index('friend_id');
        });

        // 9. Friend Requests Table
        Schema::create('friend_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('receiver_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('pending')->index(); // 'pending', 'accepted', 'rejected'
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->unique(['sender_id', 'receiver_id']);
            $table->index(['receiver_id', 'status']);
        });

        // 10. Achievements Table
        Schema::create('achievements', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug', 80)->unique();
            $table->string('description');
            $table->string('icon', 50)->default('trophy');
            $table->string('badge_color', 40)->default('purple');
            $table->unsignedInteger('points_reward')->default(50);
            $table->string('criteria_type', 40); // 'games_played', 'games_won', 'total_points', 'win_streak', 'friends_count', 'game_specific_wins'
            $table->unsignedInteger('criteria_value')->default(1);
            $table->foreignId('game_id')->nullable()->constrained('games')->nullOnDelete();
            $table->timestamps();
        });

        // 11. Player Achievements Table
        Schema::create('player_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('achievement_id')->constrained('achievements')->cascadeOnDelete();
            $table->timestamp('unlocked_at')->useCurrent();
            $table->timestamps();

            $table->unique(['user_id', 'achievement_id']);
        });

        // 12. Notifications Table
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 50)->index(); // 'room_invite', 'friend_request', 'friend_accepted', 'achievement_unlocked', 'match_result', 'system'
            $table->string('title');
            $table->text('message');
            $table->string('action_url')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
        });

        // 13. Reports Table (Admin Moderation & Player Reports)
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reported_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('game_room_id')->nullable()->constrained('game_rooms')->nullOnDelete();
            $table->string('reason', 100);
            $table->text('details')->nullable();
            $table->string('status', 20)->default('open')->index(); // 'open', 'resolved', 'dismissed'
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('admin_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('player_achievements');
        Schema::dropIfExists('achievements');
        Schema::dropIfExists('friend_requests');
        Schema::dropIfExists('friendships');
        Schema::dropIfExists('leaderboards');
        Schema::dropIfExists('player_statistics');
        Schema::dropIfExists('game_moves');
        Schema::dropIfExists('game_sessions');
        Schema::dropIfExists('room_players');
        Schema::dropIfExists('game_rooms');
        Schema::dropIfExists('games');
    }
};
