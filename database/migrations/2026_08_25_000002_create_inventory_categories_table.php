<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5 Part 1 - inventory categories, replacing the free-text
 * `inventory_items.category` string with a real table so the order guide can be
 * grouped and re-ordered from the UI.
 *
 * Categories are reference data shared by every store, so they carry no store_id.
 * The canonical eight rows are inserted here (frozen at this point in time) so
 * that the backfill in 2026_08_25_000003 has something to map onto;
 * InventoryCategoriesSeeder keeps the live list idempotently in sync.
 */
return new class extends Migration
{
    private const CATEGORIES = [
        ['name' => 'Meats', 'display_order' => 10],
        ['name' => 'Breads', 'display_order' => 20],
        ['name' => 'Cheese', 'display_order' => 30],
        ['name' => 'Sides', 'display_order' => 40],
        ['name' => 'Veggies', 'display_order' => 50],
        ['name' => 'Canned Goods & Misc', 'display_order' => 60],
        ['name' => 'Paper Goods', 'display_order' => 70],
        ['name' => 'Beverages', 'display_order' => 80],
    ];

    public function up(): void
    {
        Schema::create('inventory_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();

            $table->unique('name');
            $table->index('display_order');
        });

        $now = now();

        DB::table('inventory_categories')->insert(array_map(
            fn (array $row) => $row + ['created_at' => $now, 'updated_at' => $now],
            self::CATEGORIES
        ));
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_categories');
    }
};
