<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Phase 5 Part 1 - data migration, not schema.
 *
 * Before this table existed, "which vendors sell this item" was implied by the
 * rows in `vendor_prices` plus `inventory_items.preferred_vendor_id`. Leaving the
 * new pivot empty would make it disagree with data the app already relies on, so
 * the existing pairs are copied across: latest dated price per (item, vendor)
 * becomes `current_price` / `price_updated_at`, and the item's preferred vendor
 * is flagged.
 *
 * Safe to drop if you would rather start the pivot empty; nothing else depends on it.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $rows = [];

        // Ascending by date, so the last write per pair is the newest price.
        DB::table('vendor_prices')
            ->orderBy('effective_date')
            ->orderBy('id')
            ->select('inventory_item_id', 'vendor_id', 'price', 'effective_date')
            ->each(function ($price) use (&$rows, $now) {
                $rows["{$price->inventory_item_id}:{$price->vendor_id}"] = [
                    'inventory_item_id' => $price->inventory_item_id,
                    'vendor_id' => $price->vendor_id,
                    'vendor_sku' => null,
                    'current_price' => $price->price,
                    'price_updated_at' => $price->effective_date,
                    'is_preferred_vendor' => false,
                    'notes' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            });

        // A preferred vendor is a vendor of that item even with no price on file.
        DB::table('inventory_items')
            ->whereNotNull('preferred_vendor_id')
            ->orderBy('id')
            ->select('id', 'preferred_vendor_id')
            ->each(function ($item) use (&$rows, $now) {
                $key = "{$item->id}:{$item->preferred_vendor_id}";

                $rows[$key] ??= [
                    'inventory_item_id' => $item->id,
                    'vendor_id' => $item->preferred_vendor_id,
                    'vendor_sku' => null,
                    'current_price' => null,
                    'price_updated_at' => null,
                    'notes' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $rows[$key]['is_preferred_vendor'] = true;
            });

        foreach (array_chunk(array_values($rows), 500) as $chunk) {
            DB::table('inventory_item_vendor')->insertOrIgnore($chunk);
        }
    }

    public function down(): void
    {
        // Reversing would delete rows an admin may have edited since; the table
        // drop in 2026_08_25_000004 already removes everything.
    }
};
