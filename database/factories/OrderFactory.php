<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'vendor_id' => null,
            'week_start_date' => now()->startOfWeek()->toDateString(),
            'order_sequence' => 1,
            'status' => 'draft',
            'created_by' => null,
        ];
    }

    public function received(): static
    {
        return $this->state(fn () => ['status' => 'received', 'received_at' => now()]);
    }
}
