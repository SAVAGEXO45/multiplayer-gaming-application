<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('achievement_id')->constrained()->cascadeOnDelete();
            $table->timestamp('unlocked_at');
            $table->timestamps();

            $table->unique(['user_id', 'achievement_id']);
            $table->index(['user_id', 'unlocked_at']);
        });

        Schema::create('reward_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reward_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 16);
            $table->integer('amount');
            $table->integer('balance_after');
            $table->string('reason');
            $table->string('reference_type', 32)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
            $table->unique(
                ['reference_type', 'reference_id', 'user_id'],
                'reward_transactions_reference_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reward_transactions');
        Schema::dropIfExists('user_achievements');
    }
};
