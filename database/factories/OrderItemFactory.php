<?php

namespace Database\Factories;

use App\Models\InventoryItem;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'inventory_item_id' => InventoryItem::factory(),
            'quantity' => 0,
            'unit' => 'each',
            'unit_price' => null,
        ];
    }
}
