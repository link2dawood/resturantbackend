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
        Schema::create('third_party_statements', function (Blueprint $table) {
            $table->id();
            $table->enum('platform', ['grubhub', 'ubereats', 'doordash', 'postmates', 'other']);
            $table->foreignId('store_id')->constrained('stores')->onDelete('cascade');
            $table->date('statement_date');
            $table->string('statement_id', 100)->nullable(); // External statement ID
            $table->decimal('gross_sales', 10, 2)->default(0);
            $table->decimal('marketing_fees', 10, 2)->default(0);
            $table->decimal('delivery_fees', 10, 2)->default(0);
            $table->decimal('processing_fees', 10, 2)->default(0);
            $table->decimal('net_deposit', 10, 2)->default(0);
            $table->decimal('sales_tax_collected', 10, 2)->default(0);
            $table->foreignId('import_batch_id')->nullable()->constrained('import_batches')->onDelete('set null');
            $table->string('file_name')->nullable();
            $table->string('file_hash')->nullable();
            $table->foreignId('imported_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index(['platform', 'statement_date']);
            $table->index('store_id');
            $table->index('statement_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('third_party_statements');
    }
};
