<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Stores the path to the uploaded file in storage (so the file can be deleted when the statement is removed).
     */
    public function up(): void
    {
        Schema::table('third_party_statements', function (Blueprint $table) {
            $table->string('file_path', 500)->nullable()->after('file_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('third_party_statements', function (Blueprint $table) {
            $table->dropColumn('file_path');
        });
    }
};
