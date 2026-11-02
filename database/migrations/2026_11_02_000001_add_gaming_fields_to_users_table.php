<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 32)->nullable()->unique()->after('name');
            $table->string('avatar_path')->nullable()->after('password');
            $table->text('bio')->nullable()->after('avatar_path');
            $table->string('role', 16)->default('player')->index()->after('bio');
            $table->boolean('is_suspended')->default(false)->index()->after('role');
            $table->timestamp('suspended_at')->nullable()->after('is_suspended');
            $table->string('suspension_reason')->nullable()->after('suspended_at');
            $table->timestamp('last_seen_at')->nullable()->after('suspension_reason');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'username',
                'avatar_path',
                'bio',
                'role',
                'is_suspended',
                'suspended_at',
                'suspension_reason',
                'last_seen_at',
            ]);
        });
    }
};
