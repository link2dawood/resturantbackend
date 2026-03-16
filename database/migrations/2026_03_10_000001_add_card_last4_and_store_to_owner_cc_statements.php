<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('owner_cc_statement_imports', function (Blueprint $table) {
            $table->string('card_last4', 4)->nullable()->after('file_path');
        });

        Schema::table('owner_cc_statement_lines', function (Blueprint $table) {
            $table->string('card_last4', 4)->nullable()->after('status');
            $table->foreignId('store_id')->nullable()->after('coa_id')->constrained('stores')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('owner_cc_statement_imports', function (Blueprint $table) {
            $table->dropColumn('card_last4');
        });

        Schema::table('owner_cc_statement_lines', function (Blueprint $table) {
            $table->dropForeign(['store_id']);
            $table->dropColumn('store_id');
            $table->dropColumn('card_last4');
        });
    }
};
