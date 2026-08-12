<?php

namespace Database\Factories;

use App\Models\InventoryItem;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryStockFactory extends Factory
{
    public function definition(): array
    {
        return [
            'inventory_item_id' => InventoryItem::factory(),
            'store_id' => Store::factory(),
            'week_start_date' => now()->startOfWeek()->toDateString(),
            'starting_stock' => 0,
            'actual_ending_stock' => null,
            'status' => 'draft',
        ];
    }
}
