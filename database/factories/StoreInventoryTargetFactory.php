<?php

namespace Database\Factories;

use App\Models\StoreInventoryTarget;
use Illuminate\Database\Eloquent\Factories\Factory;

class StoreInventoryTargetFactory extends Factory
{
    protected $model = StoreInventoryTarget::class;

    public function definition(): array
    {
        return [
            'target_stock_level' => 10,
            'min_stock_level' => 3,
        ];
    }
}
