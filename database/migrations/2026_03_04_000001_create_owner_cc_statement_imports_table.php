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
        Schema::create('owner_cc_statement_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('imported_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('store_id')->nullable()->constrained('stores')->onDelete('set null');
            $table->string('file_name', 255);
            $table->string('file_hash', 64)->index();
            $table->unsignedInteger('rows_imported')->default(0);
            $table->timestamps();

            $table->index('imported_by');
            $table->index('store_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('owner_cc_statement_imports');
    }
};
