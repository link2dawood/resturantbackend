<?php

namespace Database\Seeders;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Store;
use App\Models\Vendor;
use App\Services\Inventory\ItemVendorMapper;
use Illuminate\Database\Seeder;

/**
 * Phase 5 — ten sample items taken from the client's paper order guide, seeded
 * into every store. Idempotent: matched on (store, name), so re-seeding updates
 * the pack size rather than creating a second row.
 *
 * "portions" is the number of base units in one purchase unit, which is what
 * UnitConverter reads. Steak: base_unit "portion", 53 portions in a box, each
 * portion 3 oz, so a box is about 159 oz, roughly the 10 lb box the client buys.
 */
class InventoryItemsSeeder extends Seeder
{
    public function __construct(private ?ItemVendorMapper $mapper = null)
    {
        $this->mapper ??= app(ItemVendorMapper::class);
    }

    public function run(): void
    {
        $stores = Store::all();

        if ($stores->isEmpty()) {
            $this->command?->warn('No stores found, skipping inventory item seeding.');

            return;
        }

        $categories = InventoryCategory::all()->keyBy('name');
        $vendors = Vendor::all()->keyBy('vendor_name');

        foreach ($stores as $store) {
            foreach ($this->items() as $definition) {
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
                    'category' => $category->name,
                    'base_unit' => $definition['base_unit'],
                    'purchase_unit' => $definition['purchase_unit'],
                    'units_per_purchase' => $definition['portions'],
                    'portion_size' => $definition['portion_size'],
                    'portion_unit' => $definition['portion_unit'],
                    'is_active' => true,
                ]);
                $item->save();

                // Every vendor that sells it, with that vendor's SKU and price.
                // The preferred one is flagged on the pivot and mirrored onto
                // preferred_vendor_id above, so both readings agree.
                $mapping = [];
                foreach ($definition['vendors'] as $vendorName => $offer) {
                    $vendor = $vendors->get($vendorName);

                    if (! $vendor) {
                        continue;
                    }

                    $mapping[$vendor->id] = [
                        'enabled' => true,
                        'vendor_sku' => $offer['sku'],
                        'current_price' => $offer['price'],
                        'is_preferred' => $vendorName === $definition['preferred_vendor'],
                    ];
                }

                if (! empty($mapping)) {
                    $this->mapper->sync($item->fresh(), $mapping);
                }
            }
        }

        $this->command?->info('Seeded '.count($this->items()).' inventory items across '.$stores->count().' store(s).');
    }

    /**
     * Prices are the client's quoted price per purchase unit; a second vendor is
     * priced slightly higher so the cheapest-vendor comparison has something to
     * choose between.
     *
     * @return list<array<string, mixed>>
     */
    private function items(): array
    {
        return [
            [
                'name' => 'Steak', 'category' => 'Meats',
                'base_unit' => 'portion', 'purchase_unit' => 'box', 'portions' => 53,
                'portion_size' => 3.00, 'portion_unit' => 'oz',
                'preferred_vendor' => 'Lisanti',
                'vendors' => [
                    'Lisanti' => ['sku' => 'LIS-STK-10', 'price' => 145.00],
                    'Restaurant Depot' => ['sku' => 'RD-88214', 'price' => 152.50],
                ],
            ],
            [
                'name' => 'Chicken', 'category' => 'Meats',
                'base_unit' => 'lb', 'purchase_unit' => 'box', 'portions' => 40,
                'portion_size' => null, 'portion_unit' => null,
                'preferred_vendor' => 'Lisanti',
                'vendors' => [
                    'Lisanti' => ['sku' => 'LIS-CHK-40', 'price' => 96.00],
                    'Restaurant Depot' => ['sku' => 'RD-40119', 'price' => 92.75],
                ],
            ],
            [
                'name' => 'Gyro', 'category' => 'Meats',
                'base_unit' => 'each', 'purchase_unit' => 'box', 'portions' => 20,
                'portion_size' => null, 'portion_unit' => null,
                'preferred_vendor' => 'Lisanti',
                'vendors' => [
                    'Lisanti' => ['sku' => 'LIS-GYR-20', 'price' => 78.00],
                ],
            ],
            [
                'name' => '8" Bread', 'category' => 'Breads',
                'base_unit' => 'each', 'purchase_unit' => 'case', 'portions' => 60,
                'portion_size' => null, 'portion_unit' => null,
                'preferred_vendor' => 'Lisanti',
                'vendors' => [
                    'Lisanti' => ['sku' => 'LIS-BR8', 'price' => 32.00],
                ],
            ],
            [
                'name' => '10" Bread', 'category' => 'Breads',
                'base_unit' => 'each', 'purchase_unit' => 'case', 'portions' => 48,
                'portion_size' => null, 'portion_unit' => null,
                'preferred_vendor' => 'Lisanti',
                'vendors' => [
                    'Lisanti' => ['sku' => 'LIS-BR10', 'price' => 34.50],
                ],
            ],
            [
                'name' => 'Pita', 'category' => 'Breads',
                'base_unit' => 'each', 'purchase_unit' => 'case', 'portions' => 120,
                'portion_size' => null, 'portion_unit' => null,
                'preferred_vendor' => 'Lisanti',
                'vendors' => [
                    'Lisanti' => ['sku' => 'LIS-PITA-120', 'price' => 41.00],
                    'Restaurant Depot' => ['sku' => 'RD-77310', 'price' => 44.25],
                ],
            ],
            [
                'name' => 'Provolone', 'category' => 'Cheese',
                'base_unit' => 'lb', 'purchase_unit' => 'case', 'portions' => 20,
                'portion_size' => null, 'portion_unit' => null,
                'preferred_vendor' => 'Lisanti',
                'vendors' => [
                    'Lisanti' => ['sku' => 'LIS-PROV-20', 'price' => 89.00],
                ],
            ],
            [
                // Was bought from Nogales Produce, which the client retired.
                'name' => 'Onions', 'category' => 'Veggies',
                'base_unit' => 'lb', 'purchase_unit' => 'bag', 'portions' => 50,
                'portion_size' => null, 'portion_unit' => null,
                'preferred_vendor' => 'Restaurant Depot',
                'vendors' => [
                    'Restaurant Depot' => ['sku' => 'RD-ONI-50', 'price' => 28.00],
                ],
            ],
            [
                'name' => 'Frying Oil', 'category' => 'Canned Goods & Misc',
                'base_unit' => 'lb', 'purchase_unit' => 'jug', 'portions' => 35,
                'portion_size' => null, 'portion_unit' => null,
                'preferred_vendor' => "Sam's Club",
                'vendors' => [
                    "Sam's Club" => ['sku' => 'SC-OIL-35', 'price' => 42.00],
                    'Lisanti' => ['sku' => 'LIS-OIL-35', 'price' => 46.50],
                    'Restaurant Depot' => ['sku' => 'RD-OIL-35', 'price' => 43.75],
                ],
            ],
            [
                'name' => 'Coke', 'category' => 'Beverages',
                'base_unit' => 'each', 'purchase_unit' => 'case', 'portions' => 24,
                'portion_size' => null, 'portion_unit' => null,
                'preferred_vendor' => 'Coca-Cola',
                'vendors' => [
                    'Coca-Cola' => ['sku' => 'CC-CLS-24', 'price' => 18.00],
                ],
            ],
        ];
    }
}
