<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('third_party_statements', function (Blueprint $table) {
            $table->decimal('adjustments', 10, 2)->default(0)->after('processing_fees');
        });
    }

    public function down(): void
    {
        Schema::table('third_party_statements', function (Blueprint $table) {
            $table->dropColumn('adjustments');
        });
    }
};

