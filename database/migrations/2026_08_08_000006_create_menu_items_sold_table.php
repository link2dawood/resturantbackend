<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5: rows from the Square "Items Sold" CSV. Unmatched rows keep
 * menu_item_id = null (is_matched = false) until an admin maps them; unmatched
 * rows are excluded from variance usage and surfaced as a warning.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items_sold', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->date('week_start_date');
            $table->foreignId('menu_item_id')->nullable()->constrained('menu_items')->nullOnDelete();
            $table->string('size_variant', 20)->nullable();
            $table->string('square_raw_name', 191);
            $table->decimal('quantity_sold', 14, 4)->default(0);
            $table->string('import_batch_id', 100)->nullable();
            $table->boolean('is_matched')->default(false);
            $table->timestamps();

            $table->index(['store_id', 'week_start_date']);
            $table->index('menu_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items_sold');
    }
};
