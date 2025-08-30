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
        Schema::table('daily_reports', function (Blueprint $table) {
            // Composite index for store and date queries
            $table->index(['store_id', 'report_date'], 'idx_store_date');
            
            // Index for created_by for filtering by user
            $table->index('created_by', 'idx_created_by');
            
            // Index for report_date for date range queries
            $table->index('report_date', 'idx_report_date');
            
            // Index for created_at for recent reports
            $table->index('created_at', 'idx_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_reports', function (Blueprint $table) {
            $table->dropIndex('idx_store_date');
            $table->dropIndex('idx_created_by');
            $table->dropIndex('idx_report_date');
            $table->dropIndex('idx_created_at');
        });
    }
};
