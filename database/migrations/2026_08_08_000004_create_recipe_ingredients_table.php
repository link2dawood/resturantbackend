<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5: the N ingredients of a recipe. quantity_base is the portion in the
 * inventory item's BASE unit (already converted); entered_quantity/entered_unit
 * preserve what the admin typed for display (e.g. "1.5 portions × 3 oz").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipe_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained('recipes')->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->decimal('quantity_base', 14, 4); // portion in the item's base unit
            $table->decimal('entered_quantity', 14, 4)->nullable();
            $table->string('entered_unit', 20)->nullable();
            $table->timestamps();

            $table->index('recipe_id');
            $table->index('inventory_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_ingredients');
    }
};
