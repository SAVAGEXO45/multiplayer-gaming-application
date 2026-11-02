<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('achievements', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->string('name');
            $table->string('description');
            $table->string('icon', 64)->default('trophy');
            $table->string('tier', 16)->default('bronze');
            $table->string('criteria_type', 32);
            $table->unsignedInteger('criteria_value');
            $table->unsignedSmallInteger('points_reward')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'criteria_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('achievements');
    }
};
