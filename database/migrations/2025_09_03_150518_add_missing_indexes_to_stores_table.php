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
        Schema::table('stores', function (Blueprint $table) {
            // Add index on created_by for owner-store relationships
            $table->index('created_by', 'idx_stores_created_by');

            // Add index on city/state for geographic queries
            $table->index(['city', 'state'], 'idx_stores_city_state');

            // Add index on store_info for search functionality
            $table->index('store_info', 'idx_stores_store_info');

            // Add index on created_at for chronological queries
            $table->index('created_at', 'idx_stores_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropIndex('idx_stores_created_by');
            $table->dropIndex('idx_stores_city_state');
            $table->dropIndex('idx_stores_store_info');
            $table->dropIndex('idx_stores_created_at');
        });
    }
};
