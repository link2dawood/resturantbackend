<?php

namespace Database\Seeders;

use App\Models\InventoryItem;
use App\Models\Store;
use App\Models\StoreInventoryTarget;
use Illuminate\Database\Seeder;

/**
 * Phase 5 — sample stock targets for the FIRST store only, deliberately.
 *
 * The point of the feature is that stores differ, so seeding every store with
 * the same numbers would hide the behaviour. One store gets realistic targets;
 * the others stay empty so "copy from another store" has something to do.
 *
 * Levels are in each item's purchase unit: 15 means 15 boxes of steak.
 * Idempotent, matched on (store, item).
 */
class StoreInventoryTargetsSeeder extends Seeder
{
    /** item name => [target, reorder point] in purchase units */
    private const TARGETS = [
        'Steak' => [15, 4],
        'Chicken' => [8, 2],
        'Gyro' => [4, 1],
        '8" Bread' => [12, 3],
        '10" Bread' => [6, 2],
        'Pita' => [5, 1],
        'Provolone' => [6, 2],
        'Onions' => [4, 1],
        'Frying Oil' => [10, 3],
        'Coke' => [20, 6],
    ];

    public function run(): void
    {
        $store = Store::orderBy('id')->first();

        if (! $store) {
            $this->command?->warn('No stores found, skipping inventory target seeding.');

            return;
        }

        $items = InventoryItem::where('store_id', $store->id)->get()->keyBy('name');
        $written = 0;

        foreach (self::TARGETS as $name => [$target, $min]) {
            $item = $items->get($name);

            if (! $item) {
                continue;
            }

            StoreInventoryTarget::updateOrCreate(
                ['store_id' => $store->id, 'inventory_item_id' => $item->id],
                ['target_stock_level' => $target, 'min_stock_level' => $min]
            );
            $written++;
        }

        $this->command?->info("Seeded {$written} stock target(s) for {$store->store_info}.");
    }
}
