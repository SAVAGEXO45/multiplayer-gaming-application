<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->string('result', 8);
            $table->integer('score')->default(0);
            $table->integer('points_awarded')->default(0);
            $table->boolean('is_multiplayer')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['game_session_id', 'user_id']);
            $table->index(['user_id', 'created_at']);
            $table->index(['game_id', 'result']);
            $table->index(['created_at', 'result']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_results');
    }
};
