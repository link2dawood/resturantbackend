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
            // Add index on created_by for user-report relationships
            $table->index('created_by', 'idx_daily_reports_created_by');
            
            // Add index on report_date for date-based queries
            $table->index('report_date', 'idx_daily_reports_report_date');
            
            // Add composite index for common report queries
            $table->index(['store_id', 'report_date'], 'idx_daily_reports_store_date');
            
            // Add index for financial analysis queries
            $table->index(['report_date', 'gross_sales'], 'idx_daily_reports_date_sales');
            
            // Add index on created_at for chronological queries
            $table->index('created_at', 'idx_daily_reports_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_reports', function (Blueprint $table) {
            $table->dropIndex('idx_daily_reports_created_by');
            $table->dropIndex('idx_daily_reports_report_date');
            $table->dropIndex('idx_daily_reports_store_date');
            $table->dropIndex('idx_daily_reports_date_sales');
            $table->dropIndex('idx_daily_reports_created_at');
        });
    }
};
