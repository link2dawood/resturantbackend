<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE owner_cc_description_mappings MODIFY transaction_type_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE owner_cc_description_mappings MODIFY transaction_type_id BIGINT UNSIGNED NOT NULL');
    }
};
