<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rewards', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->string('name');
            $table->string('description');
            $table->string('type', 24)->default('title');
            $table->string('icon', 64)->default('gift');
            $table->unsignedInteger('cost');
            $table->string('payload')->nullable();
            $table->unsignedInteger('stock')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'cost']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rewards');
    }
};
