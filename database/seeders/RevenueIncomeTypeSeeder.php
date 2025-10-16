<?php

namespace Database\Seeders;

use App\Models\RevenueIncomeType;
use Illuminate\Database\Seeder;

class RevenueIncomeTypeSeeder extends Seeder
{
    public function run(): void
    {
        $revenueTypes = [
            [
                'name' => 'Cash',
                'description' => 'Cash Sales',
                'category' => 'cash',
                'is_active' => true,
                'sort_order' => 0,
            ],
            [
                'name' => 'Credit Card - Square',
                'description' => 'Credit card sales through square terminal',
                'category' => 'card',
                'is_active' => true,
                'sort_order' => 0,
            ],
            [
                'name' => 'Ubereats',
                'description' => 'Ubereats online ordering platform',
                'category' => 'online',
                'is_active' => true,
                'sort_order' => 0,
            ],
            [
                'name' => 'Doordash',
                'description' => 'Doordash online ordering platform',
                'category' => 'online',
                'is_active' => true,
                'sort_order' => 0,
            ],
            [
                'name' => 'Grubhub',
                'description' => 'Grubhub online ordering platform',
                'category' => 'online',
                'is_active' => true,
                'sort_order' => 0,
            ],
            [
                'name' => 'EZ Catering',
                'description' => 'Relish and EZ Catering online ordering platform',
                'category' => 'online',
                'is_active' => true,
                'sort_order' => 0,
            ],
        ];

        foreach ($revenueTypes as $type) {
            RevenueIncomeType::create($type);
        }
    }
}
