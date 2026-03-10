<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds file_path so we know where the uploaded CC statement file is stored on disk.
     */
    public function up(): void
    {
        Schema::table('owner_cc_statement_imports', function (Blueprint $table) {
            $table->string('file_path', 500)->nullable()->after('file_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('owner_cc_statement_imports', function (Blueprint $table) {
            $table->dropColumn('file_path');
        });
    }
};

