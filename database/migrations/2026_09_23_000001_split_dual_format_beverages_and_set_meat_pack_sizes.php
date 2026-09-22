<?php

use App\Models\InventoryCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Phase 5 Part 2, from the 2026-09-22 client meeting.
 *
 * 1. Six drinks are bought in two formats: bag-in-box for the fountain and 20oz
 *    bottles. They are different products with different pack sizes, prices and
 *    order lines, so each becomes two items. The existing row is relabelled as
 *    the bag-in-box, which keeps its counts, targets, prices and order history,
 *    and a bottle row is added beside it.
 *
 * 2. "Hamburger meat partial cannot exceed 40", so the item needs a pack size
 *    of 40 for that cap to exist at all. Steak (53), chicken (40), gyro (20),
 *    the breads and pita already carry theirs.
 *
 * Additive and idempotent: nothing is deleted, and a second run finds the
 * renamed rows and the bottle rows already there.
 */
return new class extends Migration
{
    /** Drinks the client named as coming in both formats. */
    private const DUAL_FORMAT = [
        'Coke', 'Coke Zero', 'Diet Coke', 'Dr. Pepper', 'Diet Dr. Pepper', 'Sprite',
    ];

    private const BIB = ' (Bag-in-Box)';

    private const BOTTLE = ' (20oz Bottle)';

    public function up(): void
    {
        DB::table('inventory_items')
            ->whereRaw('LOWER(name) = ?', ['hamburger meat'])
            ->where('units_per_purchase', '<=', 1)
            ->update(['units_per_purchase' => 40, 'base_unit' => 'each', 'purchase_unit' => 'case']);

        $beverageCategoryId = InventoryCategory::where('name', 'Beverages')->value('id');

        foreach (self::DUAL_FORMAT as $drink) {
            // One row per store: these items are store-scoped.
            $existing = DB::table('inventory_items')
                ->whereNull('deleted_at')
                ->whereRaw('LOWER(name) = ?', [strtolower($drink)])
                ->get();

            foreach ($existing as $item) {
                DB::table('inventory_items')->where('id', $item->id)->update([
                    'name' => $drink.self::BIB,
                    'updated_at' => now(),
                ]);

                $this->addBottle($item, $drink);
            }

            // Diet Dr. Pepper is not in the order guide, so the pair has to be
            // created from scratch for every store that stocks the others.
            if ($existing->isEmpty()) {
                $this->createPairForEveryStoreWith($drink, $beverageCategoryId);
            }
        }
    }

    private function addBottle(object $source, string $drink): void
    {
        $bottleName = $drink.self::BOTTLE;

        $exists = DB::table('inventory_items')
            ->where('store_id', $source->store_id)
            ->whereRaw('LOWER(name) = ?', [strtolower($bottleName)])
            ->exists();

        if ($exists) {
            return;
        }

        $row = (array) $source;
        unset($row['id']);

        DB::table('inventory_items')->insert(array_merge($row, [
            'name' => $bottleName,
            'base_unit' => 'each',
            'purchase_unit' => 'case',
            // A bottle case size varies by supplier, so it stays 1 until the
            // client confirms it, the same as the other unknowns.
            'units_per_purchase' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    /** Build both formats of a drink the order guide never listed. */
    private function createPairForEveryStoreWith(string $drink, ?int $categoryId): void
    {
        // Model it on Dr. Pepper, same supplier and pack. If that row is not
        // there, any beverage in the store will do: the point is to land the
        // item in the right store and category, not to copy a price.
        $templates = DB::table('inventory_items')
            ->whereNull('deleted_at')
            ->whereRaw('LOWER(name) = ?', ['dr. pepper (bag-in-box)'])
            ->get();

        if ($templates->isEmpty() && $categoryId !== null) {
            $templates = DB::table('inventory_items')
                ->whereNull('deleted_at')
                ->where('inventory_category_id', $categoryId)
                ->get()
                ->unique('store_id')
                ->values();
        }

        foreach ($templates as $template) {
            foreach ([self::BIB, self::BOTTLE] as $suffix) {
                $name = $drink.$suffix;

                $exists = DB::table('inventory_items')
                    ->where('store_id', $template->store_id)
                    ->whereRaw('LOWER(name) = ?', [strtolower($name)])
                    ->exists();

                if ($exists) {
                    continue;
                }

                $row = (array) $template;
                unset($row['id']);

                DB::table('inventory_items')->insert(array_merge($row, [
                    'name' => $name,
                    'inventory_category_id' => $categoryId ?? $template->inventory_category_id,
                    'base_unit' => 'each',
                    'purchase_unit' => 'case',
                    'units_per_purchase' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    public function down(): void
    {
        // The bottle rows may have been counted by now, so dropping them would
        // lose real stock history. Only the relabelling is undone.
        foreach (self::DUAL_FORMAT as $drink) {
            DB::table('inventory_items')
                ->whereRaw('LOWER(name) = ?', [strtolower($drink.self::BIB)])
                ->update(['name' => $drink, 'updated_at' => now()]);
        }
    }
};
