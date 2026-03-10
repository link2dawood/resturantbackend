<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('owner_cc_statement_lines', function (Blueprint $table) {
            $table->foreignId('transaction_type_id')
                ->nullable()
                ->after('member_name')
                ->constrained('transaction_types')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('owner_cc_statement_lines', function (Blueprint $table) {
            $table->dropForeign(['transaction_type_id']);
        });
    }
};
