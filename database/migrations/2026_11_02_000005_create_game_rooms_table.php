<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('code', 8)->unique();
            $table->string('name');
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('host_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('max_players')->default(2);
            $table->string('status', 24)->default('waiting')->index();
            $table->boolean('is_private')->default(false);
            $table->string('password')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['game_id', 'status']);
        });

        Schema::create('room_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_ready')->default(false);
            $table->boolean('is_host')->default(false);
            $table->unsignedInteger('score')->default(0);
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->timestamps();

            $table->unique(['game_room_id', 'user_id']);
            $table->index(['game_room_id', 'left_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_players');
        Schema::dropIfExists('game_rooms');
    }
};
