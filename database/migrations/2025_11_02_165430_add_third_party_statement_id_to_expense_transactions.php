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
        Schema::table('expense_transactions', function (Blueprint $table) {
            $table->foreignId('third_party_statement_id')->nullable()->constrained('third_party_statements')->onDelete('set null')->after('daily_report_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expense_transactions', function (Blueprint $table) {
            $table->dropForeign(['third_party_statement_id']);
            $table->dropColumn('third_party_statement_id');
        });
    }
};
