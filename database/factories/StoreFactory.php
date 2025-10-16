<?php

namespace Database\Factories;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StoreFactory extends Factory
{
    protected $model = Store::class;

    public function definition()
    {
        return [
            'store_info' => $this->faker->company.' Restaurant',
            'contact_name' => $this->faker->name,
            'phone' => $this->faker->phoneNumber,
            'address' => $this->faker->streetAddress,
            'city' => $this->faker->city,
            'state' => $this->faker->stateAbbr,
            'zip' => $this->faker->postcode,
            'sales_tax_rate' => $this->faker->randomFloat(4, 0.05, 0.12), // 5% to 12%
            'medicare_tax_rate' => $this->faker->randomFloat(4, 0.01, 0.03), // 1% to 3%
            'created_by' => User::factory(),
        ];
    }
}
