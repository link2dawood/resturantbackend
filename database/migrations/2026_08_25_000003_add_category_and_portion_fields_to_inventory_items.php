<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5 Part 1 - adds the portion definition and the category foreign key to
 * `inventory_items`, plus soft deletes.
 *
 * Deliberately NOT re-added, because the table already has them under different
 * names (see 2026_08_08_000001):
 *   spec "unit"               -> existing `purchase_unit`
 *   spec "portions_per_unit"  -> existing `units_per_purchase`
 *   spec "is_active"          -> existing `is_active`
 * Adding duplicates would give the order maths two sources of truth for the
 * pack size, which is exactly the silent breakage the portion rule guards against.
 *
 * The legacy `category` string is left in place (existing controllers, views and
 * tests read it) and is backfilled into `inventory_category_id` here.
 */
return new class extends Migration
{
    /** legacy free-text category => seeded inventory_categories.name */
    private const CATEGORY_MAP = [
        'meat' => 'Meats',
        'bread' => 'Breads',
        'cheese' => 'Cheese',
        'side' => 'Sides',
        'sides' => 'Sides',
        'veg' => 'Veggies',
        'misc' => 'Canned Goods & Misc',
        'packaging' => 'Paper Goods',
        'beverage' => 'Beverages',
    ];

    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->foreignId('inventory_category_id')->nullable()->after('category')
                ->constrained('inventory_categories')->nullOnDelete();
            $table->decimal('portion_size', 10, 2)->nullable()->after('units_per_purchase');
            $table->string('portion_unit', 20)->nullable()->after('portion_size');
            $table->text('notes')->nullable()->after('is_active');
            $table->softDeletes();

            $table->index(['store_id', 'inventory_category_id']);
        });

        $categoryIds = DB::table('inventory_categories')->pluck('id', 'name');

        foreach (self::CATEGORY_MAP as $legacy => $categoryName) {
            if (! isset($categoryIds[$categoryName])) {
                continue;
            }

            DB::table('inventory_items')
                ->whereRaw('LOWER(category) = ?', [$legacy])
                ->update(['inventory_category_id' => $categoryIds[$categoryName]]);
        }
    }

    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropIndex(['store_id', 'inventory_category_id']);
            $table->dropSoftDeletes();
            $table->dropColumn(['notes', 'portion_unit', 'portion_size']);
            $table->dropConstrainedForeignId('inventory_category_id');
        });
    }
};
