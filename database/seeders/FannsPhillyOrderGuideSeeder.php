<?php

namespace Database\Seeders;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Store;
use App\Models\Vendor;
use App\Services\Inventory\ItemVendorMapper;
use Illuminate\Database\Seeder;

/**
 * Phase 5 Part 1 — the client's REAL order guide.
 *
 * Extracted from "Lisanti Order Guide12.30.24/Sheet1.html", the paper sheet the
 * manager fills in every Monday. 123 items across 8 categories, with the pack
 * sizes the sheet spells out in the item names ("Steak (53 in 1 Box)") and the
 * vendor groupings its three columns imply.
 *
 * The data is baked in rather than parsed at run time on purpose: a seeder must
 * not depend on a file under docs/ that may not be deployed, and this way the
 * exact contents are reviewable in the diff.
 *
 * Items appearing under more than one heading are genuinely multi-vendor: the
 * sheet lists Frying Oil under Lisanti, Restaurant Depot and Sam's, which is the
 * three-vendor case the brief calls out.
 *
 * Idempotent, matched on (store, name). Safe to re-run.
 */
class FannsPhillyOrderGuideSeeder extends Seeder
{
    public function __construct(private ?ItemVendorMapper $mapper = null)
    {
        $this->mapper ??= app(ItemVendorMapper::class);
    }

    public function run(): void
    {
        $stores = Store::all();

        if ($stores->isEmpty()) {
            $this->command?->warn('No stores found, skipping the order guide seed.');

            return;
        }

        $categories = InventoryCategory::all()->keyBy('name');
        $vendors = Vendor::all()->keyBy('vendor_name');
        $missingVendors = [];
        $seeded = 0;

        foreach ($stores as $store) {
            foreach (self::ITEMS as $definition) {
                $category = $categories->get($definition['category']);

                if (! $category) {
                    continue;
                }

                $item = InventoryItem::withTrashed()->firstOrNew([
                    'store_id' => $store->id,
                    'name' => $definition['name'],
                ]);

                $item->fill([
                    'inventory_category_id' => $category->id,
                    // Kept in step for the older screens that still read it.
                    'category' => $category->name,
                    'base_unit' => $definition['base_unit'],
                    'purchase_unit' => $definition['purchase_unit'],
                    'units_per_purchase' => $definition['units_per_purchase'],
                    'is_active' => true,
                ]);
                $item->save();
                $seeded++;

                // First vendor listed on the sheet is the preferred one.
                $mapping = [];

                foreach ($definition['vendors'] as $index => $vendorName) {
                    $vendor = $vendors->get($vendorName);

                    if (! $vendor) {
                        $missingVendors[$vendorName] = true;

                        continue;
                    }

                    $mapping[$vendor->id] = [
                        'enabled' => true,
                        'is_preferred' => $index === 0,
                    ];
                }

                if (! empty($mapping)) {
                    $this->mapper->sync($item->fresh(), $mapping);
                }
            }
        }

        $this->command?->info(
            'Seeded '.count(self::ITEMS).' order-guide items across '.$stores->count().' store(s) ('.$seeded.' rows).'
        );

        if (! empty($missingVendors)) {
            $this->command?->warn(
                'These vendors are not in the database, so their mappings were skipped: '
                .implode(', ', array_keys($missingVendors)).'. Run VendorsSeeder first.'
            );
        }
    }

    /**
     * Straight from the 30 December 2024 order guide.
     *
     * units_per_purchase is the number of base units in one purchase unit, which
     * is what UnitConverter reads. Where the sheet does not state a pack size it
     * is 1, meaning the item is counted in the same unit it is ordered in.
     *
     * @var list<array<string, mixed>>
     */
    private const ITEMS = [
        ['name' => 'Steak', 'category' => 'Meats', 'base_unit' => 'each', 'purchase_unit' => 'box', 'units_per_purchase' => 53, 'vendors' => ['Lisanti']],
        ['name' => 'Onions', 'category' => 'Veggies', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Lisanti']],
        ['name' => 'Coke', 'category' => 'Beverages', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Coca-Cola']],
        ['name' => 'Chicken', 'category' => 'Meats', 'base_unit' => 'lb', 'purchase_unit' => 'box', 'units_per_purchase' => 40, 'vendors' => ['Lisanti']],
        ['name' => 'Mushrooms', 'category' => 'Veggies', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Lisanti', 'Restaurant Depot']],
        ['name' => 'Diet Coke', 'category' => 'Beverages', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Coca-Cola']],
        ['name' => 'Turkey', 'category' => 'Meats', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Lisanti']],
        ['name' => 'Green Peppers', 'category' => 'Veggies', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Lisanti', 'Restaurant Depot']],
        ['name' => 'Dr. Pepper', 'category' => 'Beverages', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Coca-Cola']],
        ['name' => 'Gyro', 'category' => 'Meats', 'base_unit' => 'each', 'purchase_unit' => 'box', 'units_per_purchase' => 20, 'vendors' => ['Lisanti']],
        ['name' => 'Romaine', 'category' => 'Veggies', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Lisanti', 'Restaurant Depot']],
        ['name' => 'Coke Zero', 'category' => 'Beverages', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Coca-Cola']],
        ['name' => 'Bacon Crumble', 'category' => 'Meats', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Lisanti']],
        ['name' => 'Iceberg', 'category' => 'Veggies', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Lisanti', 'Restaurant Depot']],
        ['name' => 'Sprite', 'category' => 'Beverages', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Coca-Cola']],
        ['name' => 'Chicken Nuggets', 'category' => 'Meats', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Lisanti']],
        ['name' => 'Tomatoes', 'category' => 'Veggies', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Lisanti', 'Restaurant Depot']],
        ['name' => 'Root Beer', 'category' => 'Beverages', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Coca-Cola']],
        ['name' => 'Chicken Patties', 'category' => 'Meats', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Lisanti']],
        ['name' => 'Avocados', 'category' => 'Veggies', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Lisanti']],
        ['name' => 'Mountain Blast', 'category' => 'Beverages', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Coca-Cola']],
        ['name' => 'Cucumbers', 'category' => 'Veggies', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Lisanti']],
        ['name' => 'Juices Red/Blue/Purple', 'category' => 'Beverages', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Coca-Cola']],
        ['name' => 'Mexican Coke 500m', 'category' => 'Beverages', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Coca-Cola']],
        ['name' => 'Mexican Coke 355m', 'category' => 'Beverages', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Coca-Cola']],
        ['name' => '8" Bread', 'category' => 'Breads', 'base_unit' => 'each', 'purchase_unit' => 'box', 'units_per_purchase' => 60, 'vendors' => ['Lisanti']],
        ['name' => 'Napkins', 'category' => 'Paper Goods', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Lisanti']],
        ['name' => 'Hamburger Buns', 'category' => 'Breads', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Sam\'s Club', 'Lisanti']],
        ['name' => '10" Bread', 'category' => 'Breads', 'base_unit' => 'each', 'purchase_unit' => 'box', 'units_per_purchase' => 48, 'vendors' => ['Lisanti']],
        ['name' => 'Phil\'s Wrapping Paper', 'category' => 'Paper Goods', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Lisanti']],
        ['name' => 'Bacon Sliced', 'category' => 'Meats', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Sam\'s Club']],
        ['name' => 'Pita', 'category' => 'Breads', 'base_unit' => 'each', 'purchase_unit' => 'box', 'units_per_purchase' => 120, 'vendors' => ['Lisanti']],
        ['name' => 'Foil Wrapping Paper', 'category' => 'Paper Goods', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Lisanti']],
        ['name' => '10" Tortilla', 'category' => 'Breads', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Sam\'s Club']],
        ['name' => '20 oz Cups', 'category' => 'Paper Goods', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Lisanti']],
        ['name' => 'Mazzeralla Sticks', 'category' => 'Cheese', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Sam\'s Club']],
        ['name' => '16 oz lids', 'category' => 'Paper Goods', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Lisanti']],
        ['name' => 'Corn Dogs', 'category' => 'Meats', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Sam\'s Club']],
        ['name' => '32 oz Cups', 'category' => 'Paper Goods', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Lisanti']],
        ['name' => 'Parmasean Cheese', 'category' => 'Cheese', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Sam\'s Club']],
        ['name' => '32 oz Lids', 'category' => 'Paper Goods', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Lisanti']],
        ['name' => 'Yellow American Cheese', 'category' => 'Cheese', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Sam\'s Club']],
        ['name' => '3/8" French Fries', 'category' => 'Sides', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Lisanti']],
        ['name' => '#50 Food Tray', 'category' => 'Paper Goods', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Lisanti', 'Restaurant Depot']],
        ['name' => 'Cheddar Cheese', 'category' => 'Cheese', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Sam\'s Club']],
        ['name' => 'Tator Tots', 'category' => 'Sides', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Lisanti']],
        ['name' => '# 100 Food Tray', 'category' => 'Paper Goods', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Lisanti']],
        ['name' => 'Cheese Sauce', 'category' => 'Cheese', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Sam\'s Club']],
        ['name' => 'Curly Fries', 'category' => 'Sides', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Lisanti']],
        ['name' => '9 x 9 To Go Container', 'category' => 'Paper Goods', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Lisanti']],
        ['name' => 'Marinara', 'category' => 'Canned Goods & Misc', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Sam\'s Club']],
        ['name' => '2 oz Plastic Container Black', 'category' => 'Paper Goods', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Lisanti']],
        ['name' => 'Chips', 'category' => 'Sides', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Sam\'s Club']],
        ['name' => '2 oz Plastic Lids', 'category' => 'Paper Goods', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Lisanti']],
        ['name' => 'Small Water', 'category' => 'Beverages', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Sam\'s Club']],
        ['name' => '1 Oz Paper Soufflette Cups', 'category' => 'Paper Goods', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Lisanti']],
        ['name' => 'Large Water', 'category' => 'Beverages', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Sam\'s Club']],
        ['name' => '1 Oz Paper Soufflette Lids', 'category' => 'Paper Goods', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Lisanti']],
        ['name' => 'Apple Juice', 'category' => 'Beverages', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Sam\'s Club']],
        ['name' => 'Gloves Black', 'category' => 'Paper Goods', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Lisanti']],
        ['name' => 'Sweet Tea/Coke/Sprite', 'category' => 'Beverages', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Sam\'s Club']],
        ['name' => 'Provolone', 'category' => 'Cheese', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Lisanti']],
        ['name' => 'Pink Lemonade', 'category' => 'Beverages', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Sam\'s Club']],
        ['name' => 'White American', 'category' => 'Cheese', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Lisanti']],
        ['name' => 'Yellow Lemonade', 'category' => 'Beverages', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Sam\'s Club']],
        ['name' => 'Feta Cheese', 'category' => 'Cheese', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Lisanti']],
        ['name' => 'Italian Dressing Gallons', 'category' => 'Canned Goods & Misc', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Sam\'s Club']],
        ['name' => 'Lemon Pepper', 'category' => 'Canned Goods & Misc', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Sam\'s Club']],
        ['name' => 'Ketchup Cans', 'category' => 'Canned Goods & Misc', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Lisanti']],
        ['name' => 'Garlic and Minced', 'category' => 'Canned Goods & Misc', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Sam\'s Club']],
        ['name' => 'Jalapeno Sliced', 'category' => 'Canned Goods & Misc', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Lisanti']],
        ['name' => 'Paprika', 'category' => 'Canned Goods & Misc', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Sam\'s Club']],
        ['name' => 'Hamburger Meat', 'category' => 'Meats', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Restaurant Depot']],
        ['name' => 'Black Olives', 'category' => 'Canned Goods & Misc', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Lisanti']],
        ['name' => 'Ice Tea', 'category' => 'Beverages', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Sam\'s Club']],
        ['name' => '12" Tortilla', 'category' => 'Breads', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Restaurant Depot']],
        ['name' => 'Franks Red Hot', 'category' => 'Canned Goods & Misc', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Lisanti']],
        ['name' => 'Honey', 'category' => 'Canned Goods & Misc', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Sam\'s Club']],
        ['name' => 'Onion Rings', 'category' => 'Sides', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Restaurant Depot']],
        ['name' => 'Frying Oil', 'category' => 'Canned Goods & Misc', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Lisanti', 'Restaurant Depot', 'Sam\'s Club']],
        ['name' => 'Paper Towels', 'category' => 'Paper Goods', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Sam\'s Club']],
        ['name' => 'BBQ Sauce', 'category' => 'Canned Goods & Misc', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Restaurant Depot']],
        ['name' => 'Salsa Packets', 'category' => 'Canned Goods & Misc', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Lisanti']],
        ['name' => 'Paper Plates', 'category' => 'Paper Goods', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Sam\'s Club']],
        ['name' => 'Spicy Mustard', 'category' => 'Canned Goods & Misc', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Restaurant Depot']],
        ['name' => 'Sour Cream Packets', 'category' => 'Canned Goods & Misc', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Lisanti']],
        ['name' => 'Forks', 'category' => 'Paper Goods', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Sam\'s Club']],
        ['name' => 'Sriracha', 'category' => 'Canned Goods & Misc', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Restaurant Depot']],
        ['name' => 'Taziki Sauce', 'category' => 'Canned Goods & Misc', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Lisanti']],
        ['name' => 'Knives', 'category' => 'Paper Goods', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Sam\'s Club']],
        ['name' => 'Sliced Pickles', 'category' => 'Canned Goods & Misc', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Restaurant Depot']],
        ['name' => 'Banana Peppers', 'category' => 'Canned Goods & Misc', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Lisanti']],
        ['name' => 'Lousiana Hot Sauce', 'category' => 'Canned Goods & Misc', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Sam\'s Club']],
        ['name' => 'Scott\'s Brite', 'category' => 'Canned Goods & Misc', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Restaurant Depot']],
        ['name' => 'Mayo Gallons', 'category' => 'Canned Goods & Misc', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Lisanti']],
        ['name' => 'Gloves - Plastic', 'category' => 'Paper Goods', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Sam\'s Club']],
        ['name' => 'Ranch Gallons', 'category' => 'Canned Goods & Misc', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Lisanti']],
        ['name' => '12 oz Cups', 'category' => 'Paper Goods', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Sam\'s Club']],
        ['name' => 'Mayo Packets', 'category' => 'Canned Goods & Misc', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Lisanti']],
        ['name' => 'Straws', 'category' => 'Paper Goods', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Sam\'s Club']],
        ['name' => 'Ranch Packets', 'category' => 'Canned Goods & Misc', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Lisanti']],
        ['name' => 'Plastic Bags', 'category' => 'Paper Goods', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Sam\'s Club']],
        ['name' => 'Cesar Packets', 'category' => 'Canned Goods & Misc', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Lisanti']],
        ['name' => '8 x 8 To Go Container', 'category' => 'Paper Goods', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Sam\'s Club']],
        ['name' => 'Chips Miss Vickies/Plain', 'category' => 'Canned Goods & Misc', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Lisanti']],
        ['name' => '6 x 6 To Go Container', 'category' => 'Paper Goods', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Sam\'s Club']],
        ['name' => 'Salt', 'category' => 'Canned Goods & Misc', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Lisanti']],
        ['name' => 'Trash Bags', 'category' => 'Paper Goods', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Sam\'s Club']],
        ['name' => '#100 Food Tray', 'category' => 'Paper Goods', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Restaurant Depot']],
        ['name' => 'Sugar', 'category' => 'Canned Goods & Misc', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Lisanti']],
        ['name' => 'Soap', 'category' => 'Canned Goods & Misc', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Sam\'s Club']],
        ['name' => 'Season Salt', 'category' => 'Canned Goods & Misc', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Lisanti']],
        ['name' => 'Bleach', 'category' => 'Canned Goods & Misc', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Sam\'s Club']],
        ['name' => 'Cup Holders', 'category' => 'Paper Goods', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Restaurant Depot']],
        ['name' => 'Cesar Gallons', 'category' => 'Canned Goods & Misc', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Lisanti']],
        ['name' => 'Degreaser', 'category' => 'Canned Goods & Misc', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Sam\'s Club', 'Lisanti']],
        ['name' => 'Paper Bags', 'category' => 'Paper Goods', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Restaurant Depot']],
        ['name' => 'Butter', 'category' => 'Canned Goods & Misc', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Lisanti']],
        ['name' => 'Fabuloso', 'category' => 'Canned Goods & Misc', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Sam\'s Club']],
        ['name' => 'Blended Oil', 'category' => 'Canned Goods & Misc', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Lisanti']],
        ['name' => 'Mustard Packets', 'category' => 'Canned Goods & Misc', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Sam\'s Club']],
        ['name' => 'Italian Packets', 'category' => 'Canned Goods & Misc', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Lisanti']],
        ['name' => 'Pam', 'category' => 'Canned Goods & Misc', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1, 'vendors' => ['Sam\'s Club']],
    ];
}
