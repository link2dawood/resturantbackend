<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add index on role column for frequent filtering by user type
            $table->index('role', 'idx_users_role');

            // Add index on email for faster lookups (if not already unique)
            $table->index('email', 'idx_users_email');

            // Add index on created_by for manager-owner relationships
            $table->index('created_by', 'idx_users_created_by');

            // Add index on last_online for activity tracking
            $table->index('last_online', 'idx_users_last_online');

            // Add composite index for common queries
            $table->index(['role', 'created_at'], 'idx_users_role_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_role');
            $table->dropIndex('idx_users_email');
            $table->dropIndex('idx_users_created_by');
            $table->dropIndex('idx_users_last_online');
            $table->dropIndex('idx_users_role_created_at');
        });
    }
};
