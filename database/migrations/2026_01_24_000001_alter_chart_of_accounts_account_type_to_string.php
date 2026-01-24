<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // If table exists from older migrations using ENUM, convert to VARCHAR for flexible account types.
        // This avoids requiring doctrine/dbal for ->change() and works on MySQL/MariaDB.
        DB::statement("ALTER TABLE `chart_of_accounts` MODIFY `account_type` VARCHAR(30) NOT NULL");
    }

    public function down(): void
    {
        // Irreversible safely (enum values vary by environment). Keep as VARCHAR.
    }
};


