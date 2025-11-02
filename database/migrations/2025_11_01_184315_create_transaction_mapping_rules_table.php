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
        Schema::create('transaction_mapping_rules', function (Blueprint $table) {
            $table->id();
            $table->string('description_pattern', 255)->index();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->onDelete('set null');
            $table->foreignId('coa_id')->constrained('chart_of_accounts')->onDelete('restrict');
            $table->decimal('confidence_score', 3, 2)->default(0.00)->comment('0.00 to 1.00');
            $table->integer('times_used')->default(0);
            $table->integer('times_correct')->default(0);
            $table->integer('times_incorrect')->default(0);
            $table->timestamp('last_used')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('confidence_score');
            $table->index('last_used');
            $table->index(['description_pattern', 'vendor_id'], 'pattern_vendor_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_mapping_rules');
    }
};
