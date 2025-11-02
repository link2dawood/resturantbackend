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
        Schema::create('expense_transactions', function (Blueprint $table) {
            $table->id();
            $table->enum('transaction_type', ['cash', 'credit_card', 'bank_transfer', 'check']);
            $table->date('transaction_date')->index();
            $table->date('post_date')->nullable();
            $table->foreignId('store_id')->constrained('stores')->onDelete('cascade');
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->onDelete('set null');
            $table->string('vendor_name_raw', 255)->nullable();
            $table->foreignId('coa_id')->nullable()->constrained('chart_of_accounts')->onDelete('restrict');
            $table->decimal('amount', 10, 2);
            $table->text('description')->nullable();
            $table->string('reference_number', 100)->nullable();
            $table->enum('payment_method', ['cash', 'credit_card', 'debit_card', 'check', 'eft', 'other'])->default('cash');
            $table->string('card_last_four', 4)->nullable();
            $table->string('receipt_url', 500)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_reconciled')->default(false);
            $table->timestamp('reconciled_date')->nullable();
            $table->foreignId('reconciled_by')->nullable()->constrained('users')->onDelete('set null');
            $table->boolean('needs_review')->default(false);
            $table->string('review_reason', 255)->nullable();
            $table->string('duplicate_check_hash', 64)->index();
            $table->string('import_batch_id', 100)->nullable();
            $table->foreignId('daily_report_id')->nullable()->constrained('daily_reports')->onDelete('set null');
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();
            
            // Composite indexes
            $table->index(['transaction_date', 'store_id']);
            $table->index(['needs_review', 'store_id']);
            $table->index('is_reconciled');
            $table->index('vendor_id');
            $table->index('coa_id');
            $table->index('daily_report_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_transactions');
    }
};
