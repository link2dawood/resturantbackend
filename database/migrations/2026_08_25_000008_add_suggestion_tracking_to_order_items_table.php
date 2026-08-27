<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5 Part 1 Task 9 — record what the stock-up calculator suggested next to
 * what was actually ordered, so a manual override is visible after the fact.
 *
 * Without this, an order line only says "3 boxes" and there is no way to tell
 * whether the system proposed 3 or the manager overrode a proposed 8.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('order_items', 'suggested_quantity')) {
                $table->decimal('suggested_quantity', 14, 4)->nullable()->after('quantity');
            }

            if (! Schema::hasColumn('order_items', 'is_manual_override')) {
                $table->boolean('is_manual_override')->default(false)->after('suggested_quantity');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (Schema::hasColumn('order_items', 'is_manual_override')) {
                $table->dropColumn('is_manual_override');
            }

            if (Schema::hasColumn('order_items', 'suggested_quantity')) {
                $table->dropColumn('suggested_quantity');
            }
        });
    }
};
