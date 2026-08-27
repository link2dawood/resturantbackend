<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'category' => 'meat',
            'name' => fake()->unique()->words(2, true),
            'base_unit' => 'oz',
            'purchase_unit' => 'case',
            'units_per_purchase' => 1,
            'min_stock_level' => 0,
            'safety_buffer_pct' => 0,
            'reorder_threshold' => 0,
            'is_active' => true,
        ];
    }
}
