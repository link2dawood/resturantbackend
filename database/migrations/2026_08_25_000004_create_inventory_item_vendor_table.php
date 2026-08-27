<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5 Part 1 - the many-to-many between inventory items and the vendors that
 * sell them. Steak can come from Lisanti or Restaurant Depot; frying oil from
 * Sam's Club, Lisanti or Restaurant Depot.
 *
 * `current_price` is the quick-read price used to build an order; the dated
 * history stays in `vendor_prices` (2026_08_08_000012). `price_updated_at` says
 * how stale the quick-read value is.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_item_vendor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->string('vendor_sku', 100)->nullable();
            $table->decimal('current_price', 12, 2)->nullable();
            $table->timestamp('price_updated_at')->nullable();
            $table->boolean('is_preferred_vendor')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['inventory_item_id', 'vendor_id']);
            $table->index(['vendor_id', 'is_preferred_vendor']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_item_vendor');
    }
};
