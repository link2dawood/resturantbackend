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
        Schema::table('revenue_income_types', function (Blueprint $table) {
            $table->foreignId('default_coa_id')->nullable()->constrained('chart_of_accounts')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('revenue_income_types', function (Blueprint $table) {
            $table->dropForeign(['default_coa_id']);
            $table->dropColumn('default_coa_id');
        });
    }
};
