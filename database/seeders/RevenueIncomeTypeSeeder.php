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
                'sort_order' => 1,
            ],
            [
                'name' => 'Credit Card',
                'description' => 'Credit card sales',
                'category' => 'card',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Checks (check #)',
                'description' => 'Check payments with check number',
                'category' => 'check',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Uber Eats',
                'description' => 'Uber Eats online ordering platform',
                'category' => 'online',
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'DoorDash',
                'description' => 'DoorDash online ordering platform',
                'category' => 'online',
                'is_active' => true,
                'sort_order' => 5,
            ],
            [
                'name' => 'EZ Catering',
                'description' => 'EZ Catering online ordering platform',
                'category' => 'online',
                'is_active' => true,
                'sort_order' => 6,
            ],
            [
                'name' => 'Relish',
                'description' => 'Relish online ordering platform',
                'category' => 'online',
                'is_active' => true,
                'sort_order' => 7,
            ],
            [
                'name' => 'Grubhub',
                'description' => 'Grubhub online ordering platform',
                'category' => 'online',
                'is_active' => true,
                'sort_order' => 8,
            ],
        ];

        foreach ($revenueTypes as $type) {
            RevenueIncomeType::firstOrCreate(
                ['name' => $type['name']],
                $type
            );
        }
    }
}
