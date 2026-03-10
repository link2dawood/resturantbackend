<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('owner_cc_statement_imports', function (Blueprint $table) {
            $table->unsignedInteger('rows_skipped')->default(0)->after('rows_imported');
            $table->json('import_exceptions')->nullable()->after('rows_skipped')->comment('Skipped rows with reason for exception report');
        });
    }

    public function down(): void
    {
        Schema::table('owner_cc_statement_imports', function (Blueprint $table) {
            $table->dropColumn(['rows_skipped', 'import_exceptions']);
        });
    }
};
