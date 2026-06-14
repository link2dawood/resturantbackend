<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Raw MySQL/MariaDB column modify. SQLite (test suite) can't parse MODIFY;
        // guard it so migrations run end-to-end on the in-memory test database.
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'])) {
            DB::statement('ALTER TABLE owner_cc_description_mappings MODIFY transaction_type_id BIGINT UNSIGNED NULL');
        }
    }

    public function down(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'])) {
            DB::statement('ALTER TABLE owner_cc_description_mappings MODIFY transaction_type_id BIGINT UNSIGNED NOT NULL');
        }
    }
};
