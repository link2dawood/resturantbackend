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
        Schema::dropIfExists('owner_cc_statement_lines');

        Schema::create('owner_cc_statement_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_cc_statement_import_id')->constrained('owner_cc_statement_imports')->onDelete('cascade');
            $table->string('status', 50)->nullable();
            $table->date('transaction_date')->index();
            $table->string('description', 500)->nullable();
            $table->decimal('debit', 12, 2)->default(0);
            $table->decimal('credit', 12, 2)->default(0);
            $table->string('member_name', 255)->nullable();
            $table->timestamps();

            $table->index(['owner_cc_statement_import_id', 'transaction_date'], 'occ_lines_import_date_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('owner_cc_statement_lines');
    }
};
