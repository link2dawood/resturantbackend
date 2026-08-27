<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5 Part 1 - per-store stock-up targets ("stock up to 15 boxes of steak").
 * Separate from the item so the same item can carry a different target at each
 * store, which is what drives the Monday order quantity.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_inventory_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->decimal('target_stock_level', 10, 2)->default(0);
            $table->decimal('min_stock_level', 10, 2)->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'inventory_item_id']);
            $table->index('store_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_inventory_targets');
    }
};
