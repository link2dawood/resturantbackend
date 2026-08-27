<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5: line items on a vendor order. `unit` records the unit the quantity is
 * expressed in (base_unit or the item's purchase_unit); the variance engine
 * converts explicitly to base unit and rejects any unknown unit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->decimal('quantity', 14, 4)->default(0);
            $table->string('unit', 20);
            $table->decimal('unit_price', 12, 2)->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index('inventory_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
