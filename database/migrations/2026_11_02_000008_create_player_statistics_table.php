<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('games_played')->default(0);
            $table->unsignedInteger('wins')->default(0);
            $table->unsignedInteger('losses')->default(0);
            $table->unsignedInteger('draws')->default(0);
            $table->integer('points')->default(0);
            $table->unsignedInteger('current_streak')->default(0);
            $table->unsignedInteger('best_streak')->default(0);
            $table->unsignedInteger('multiplayer_wins')->default(0);
            $table->unsignedInteger('total_score')->default(0);
            $table->timestamp('last_game_at')->nullable();
            $table->timestamps();

            $table->index(['points', 'wins']);
            $table->index(['wins', 'games_played']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_statistics');
    }
};
