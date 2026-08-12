<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5: per-store master list of trackable inventory items, with the
 * unit conversion (purchase -> base) and stock-up parameters. Quantities use
 * decimal(14,4) for variance precision (see docs variance-formula.md).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('category', 30); // meat|bread|cheese|veg|packaging|beverage
            $table->string('name', 150);
            $table->string('base_unit', 20);      // the unit ALL variance math uses (oz, lb, each, loaf)
            $table->string('purchase_unit', 20);  // how it is ordered (case, box, each)
            $table->decimal('units_per_purchase', 14, 4)->default(1); // purchase_unit -> base_unit
            $table->decimal('min_stock_level', 14, 4)->default(0);
            $table->decimal('safety_buffer_pct', 5, 2)->default(0);
            $table->decimal('reorder_threshold', 14, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['store_id', 'category']);
            $table->index(['store_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
