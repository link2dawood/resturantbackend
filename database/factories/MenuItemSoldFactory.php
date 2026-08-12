<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class MenuItemSoldFactory extends Factory
{
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'week_start_date' => now()->startOfWeek()->toDateString(),
            'menu_item_id' => null,
            'size_variant' => 'regular',
            'square_raw_name' => fake()->words(2, true),
            'quantity_sold' => 0,
            'import_batch_id' => null,
            'is_matched' => false,
        ];
    }
}
