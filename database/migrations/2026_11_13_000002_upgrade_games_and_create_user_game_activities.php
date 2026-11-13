<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->string('thumbnail')->nullable()->after('cover_gradient');
            $table->string('controls_summary')->nullable()->after('rules');
            $table->boolean('is_featured')->default(false)->after('is_active');
            $table->boolean('is_trending')->default(false)->after('is_featured');
            $table->boolean('is_new_release')->default(false)->after('is_trending');
            $table->decimal('rating', 3, 1)->default(4.8)->after('is_new_release');
            $table->unsignedInteger('plays_count')->default(0)->after('rating');
        });

        Schema::create('user_game_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_favorite')->default(false);
            $table->unsignedInteger('plays_count')->default(0);
            $table->unsignedInteger('high_score')->default(0);
            $table->unsignedInteger('max_level')->default(1);
            $table->timestamp('last_played_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'game_id']);
            $table->index(['user_id', 'is_favorite']);
            $table->index(['user_id', 'last_played_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_game_activities');

        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn([
                'thumbnail',
                'controls_summary',
                'is_featured',
                'is_trending',
                'is_new_release',
                'rating',
                'plays_count',
            ]);
        });
    }
};
