<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('owner_cc_statement_lines', function (Blueprint $table) {
            $table->foreignId('coa_id')
                ->nullable()
                ->after('transaction_type_id')
                ->constrained('chart_of_accounts')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('owner_cc_statement_lines', function (Blueprint $table) {
            $table->dropForeign(['coa_id']);
        });
    }
};
