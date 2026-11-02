<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->string('name');
            $table->string('tagline')->nullable();
            $table->text('description');
            $table->text('instructions');
            $table->text('rules');
            $table->string('icon', 64)->default('gamepad-2');
            $table->string('accent', 32)->default('cyan');
            $table->unsignedTinyInteger('min_players')->default(1);
            $table->unsignedTinyInteger('max_players')->default(2);
            $table->boolean('supports_multiplayer')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('points_win')->default(100);
            $table->unsignedSmallInteger('points_draw')->default(40);
            $table->smallInteger('points_loss')->default(-10);
            $table->unsignedSmallInteger('points_participation')->default(5);
            $table->json('difficulties')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
