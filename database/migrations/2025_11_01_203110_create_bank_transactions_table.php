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
        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_account_id')->constrained('bank_accounts')->onDelete('cascade');
            $table->date('transaction_date')->index();
            $table->date('post_date')->nullable();
            $table->text('description')->nullable();
            $table->enum('transaction_type', ['debit', 'credit']);
            $table->decimal('amount', 10, 2);
            $table->decimal('balance', 10, 2)->nullable();
            $table->string('reference_number', 100)->nullable();
            $table->foreignId('matched_expense_id')->nullable()->constrained('expense_transactions')->onDelete('set null');
            $table->foreignId('matched_revenue_id')->nullable()->constrained('daily_reports')->onDelete('set null');
            $table->enum('reconciliation_status', ['unmatched', 'matched', 'reviewed', 'exception'])->default('unmatched')->index();
            $table->text('reconciliation_notes')->nullable();
            $table->foreignId('import_batch_id')->constrained('import_batches')->onDelete('restrict');
            $table->string('duplicate_check_hash', 64)->index();
            $table->timestamps();
            
            // Indexes
            $table->index(['transaction_date', 'bank_account_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_transactions');
    }
};
