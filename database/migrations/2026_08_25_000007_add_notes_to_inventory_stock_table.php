<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5 Part 1 Task 8 — the weekly count screen lets the counter leave a note
 * against a line ("2 boxes damaged, not counted"), which the table had nowhere
 * to put.
 *
 * The rest of the "inventory_counts" shape asked for in that task already exists
 * on this table (see 2026_08_08_000005):
 *   quantity_on_hand -> starting_stock      is_submitted -> status
 *   counted_by, counted_at, week_start_date, store_id    -> unchanged
 * and the unique index on (inventory_item_id, week_start_date) already enforces
 * one row per item per week per store, since an item belongs to exactly one
 * store. A parallel table would have hidden counts from the variance engine,
 * which reads this one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_stock', function (Blueprint $table) {
            if (! Schema::hasColumn('inventory_stock', 'notes')) {
                $table->text('notes')->nullable()->after('counted_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inventory_stock', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_stock', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }
};
