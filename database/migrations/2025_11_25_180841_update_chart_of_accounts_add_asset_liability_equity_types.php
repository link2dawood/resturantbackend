<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update account_type enum to include Asset, Liability, and Equity.
        // MySQL/MariaDB requires recreating the enum via raw DDL. SQLite (the test
        // suite) has no native ENUM and stores the column as TEXT, so skip there.
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'])) {
            DB::statement("ALTER TABLE chart_of_accounts MODIFY COLUMN account_type ENUM('Asset', 'Liability', 'Equity', 'Revenue', 'COGS', 'Expense', 'Other Income') NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum values
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'])) {
            DB::statement("ALTER TABLE chart_of_accounts MODIFY COLUMN account_type ENUM('Revenue', 'COGS', 'Expense', 'Other Income') NOT NULL");
        }
    }
};
