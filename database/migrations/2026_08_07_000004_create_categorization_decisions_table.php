<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4 constraint: "Log every categorization decision (for debugging and
 * improvement)."
 *
 * One row per decision the engine or a reviewer makes:
 *  - source:   how the COA was chosen (rule | fuzzy | manual | learned | none)
 *  - decision: what happened (suggested | accepted | overridden | manual | learned)
 * plus the suggested vs. chosen COA and the confidence, so the learning loop is
 * fully auditable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorization_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->foreignId('expense_transaction_id')->nullable()->constrained('expense_transactions')->cascadeOnDelete();
            $table->foreignId('bank_transaction_id')->nullable()->constrained('bank_transactions')->cascadeOnDelete();
            $table->foreignId('transaction_mapping_rule_id')->nullable()->constrained('transaction_mapping_rules')->nullOnDelete();
            $table->string('description', 255);
            $table->string('source', 20);   // rule | fuzzy | manual | learned | none
            $table->string('decision', 20); // suggested | accepted | overridden | manual | learned
            $table->foreignId('suggested_coa_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->foreignId('chosen_coa_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->decimal('confidence', 5, 2)->nullable(); // 0.00 .. 100.00
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['owner_id', 'created_at']);
            $table->index('description');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorization_decisions');
    }
};
