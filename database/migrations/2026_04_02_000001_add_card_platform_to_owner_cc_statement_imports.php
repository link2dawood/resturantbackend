<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('owner_cc_statement_imports', function (Blueprint $table) {
            $table->string('card_platform', 64)->nullable()->after('store_id');
        });
    }

    public function down(): void
    {
        Schema::table('owner_cc_statement_imports', function (Blueprint $table) {
            $table->dropColumn('card_platform');
        });
    }
};
