<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->nullable()->constrained('game_rooms')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('status', 24)->default('active')->index();
            $table->string('difficulty', 16)->default('medium');
            $table->json('state');
            $table->foreignId('current_turn_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('winner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_draw')->default(false);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['game_id', 'status']);
            $table->index(['created_by', 'created_at']);
        });

        Schema::table('game_rooms', function (Blueprint $table) {
            $table->foreignId('current_session_id')
                ->nullable()
                ->after('status')
                ->constrained('game_sessions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('game_rooms', function (Blueprint $table) {
            $table->dropConstrainedForeignId('current_session_id');
        });

        Schema::dropIfExists('game_sessions');
    }
};
