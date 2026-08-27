<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InventoryCategoriesSeeder extends Seeder
{
    /**
     * The eight order-guide categories, in the order the client's paper sheet
     * lists them. Idempotent (matched by name) so re-seeding never duplicates
     * and display_order edits made here reach an already-migrated database.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Meats', 'display_order' => 10],
            ['name' => 'Breads', 'display_order' => 20],
            ['name' => 'Cheese', 'display_order' => 30],
            ['name' => 'Sides', 'display_order' => 40],
            ['name' => 'Veggies', 'display_order' => 50],
            ['name' => 'Canned Goods & Misc', 'display_order' => 60],
            ['name' => 'Paper Goods', 'display_order' => 70],
            ['name' => 'Beverages', 'display_order' => 80],
        ];

        foreach ($categories as $category) {
            $row = DB::table('inventory_categories')->where('name', $category['name']);

            if ($row->exists()) {
                $row->update(['display_order' => $category['display_order'], 'updated_at' => now()]);

                continue;
            }

            DB::table('inventory_categories')->insert($category + [
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
