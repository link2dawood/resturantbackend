<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5: sellable menu items. `square_name` is what the Square "Items Sold"
 * report calls the product, used to match imported sales to a menu item.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('category', 50)->nullable();
            $table->string('square_name', 191)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('store_id');
            $table->index('square_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
