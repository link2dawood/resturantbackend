<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5: a weekly vendor order. Two orders per week are supported
 * (order_sequence 1|2). Only RECEIVED orders count toward variance "ordered
 * quantity" (what actually arrived and was added to stock).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->date('week_start_date');
            $table->unsignedTinyInteger('order_sequence')->default(1); // 1 or 2
            $table->string('status', 20)->default('draft'); // draft|placed|received
            $table->timestamp('placed_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['store_id', 'week_start_date']);
            $table->index('vendor_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
