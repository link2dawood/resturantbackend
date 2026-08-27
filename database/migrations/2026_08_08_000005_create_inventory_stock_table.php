<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5: weekly stock count per item. starting_stock defaults from the prior
 * week's actual_ending_stock; actual_ending_stock is the physical count entered
 * during the week. Both in the item's base unit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->date('week_start_date'); // the Monday
            $table->decimal('starting_stock', 14, 4)->default(0);
            $table->decimal('actual_ending_stock', 14, 4)->nullable();
            $table->string('status', 20)->default('draft'); // draft|submitted
            $table->foreignId('counted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('counted_at')->nullable();
            $table->timestamps();

            $table->unique(['inventory_item_id', 'week_start_date']);
            $table->index(['store_id', 'week_start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_stock');
    }
};
