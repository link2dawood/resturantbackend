<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5.7 — per-vendor prices for inventory items, with history. Each row is a
 * price effective on a date; the current price for a (vendor, item) is the latest
 * effective_date. price_unit records the unit the price is quoted in (converted
 * to a per-base-unit price for fair comparison).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->decimal('price', 12, 2);
            $table->string('price_unit', 20);
            $table->date('effective_date');
            $table->foreignId('entered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['inventory_item_id', 'vendor_id', 'effective_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_prices');
    }
};
