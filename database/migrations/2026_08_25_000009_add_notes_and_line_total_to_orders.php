<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5 Part 1 Task 10 — the remaining columns of the specified orders /
 * order_items shape. The tables themselves shipped in 2026_08_08_000007 and
 * _000008 and already carry store_id, week_start_date, vendor_id, status,
 * placed_at, received_at, created_by, quantity and unit_price.
 *
 * Name mapping, unchanged on purpose so existing orders keep working:
 *   spec order_number      -> orders.order_sequence  (1 or 2)
 *   spec quantity_ordered  -> order_items.quantity
 *
 * The "cancelled" status needs no schema change: status is a varchar, not an
 * enum, so the new value is accepted as-is.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'notes')) {
                $table->text('notes')->nullable()->after('received_at');
            }
        });

        Schema::table('order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('order_items', 'line_total')) {
                $table->decimal('line_total', 12, 2)->nullable()->after('unit_price');
            }

            if (! Schema::hasColumn('order_items', 'notes')) {
                $table->string('notes', 255)->nullable()->after('line_total');
            }
        });

        // Backfill line totals for order lines that already have a price, so the
        // column is not a mix of computed and null on existing data.
        DB::table('order_items')
            ->whereNull('line_total')
            ->whereNotNull('unit_price')
            ->update(['line_total' => DB::raw('ROUND(quantity * unit_price, 2)')]);
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (Schema::hasColumn('order_items', 'notes')) {
                $table->dropColumn('notes');
            }

            if (Schema::hasColumn('order_items', 'line_total')) {
                $table->dropColumn('line_total');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }
};
