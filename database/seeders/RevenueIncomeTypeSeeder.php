<?php

namespace Database\Seeders;

use App\Models\RevenueIncomeType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RevenueIncomeTypeSeeder extends Seeder
{
    public function run(): void
    {
        $revenueTypes = [
            [
                'name' => 'Cash',
                'description' => 'Cash payments',
                'category' => 'cash',
                'sort_order' => 1
            ],
            [
                'name' => 'Credit Card',
                'description' => 'Credit and debit card payments',
                'category' => 'card',
                'sort_order' => 2
            ],
            [
                'name' => 'Checks',
                'description' => 'Check payments',
                'category' => 'check',
                'sort_order' => 3,
                'metadata' => ['requires_check_number' => true]
            ],
            [
                'name' => 'Uber Eats',
                'description' => 'Uber Eats delivery orders',
                'category' => 'online',
                'sort_order' => 4
            ],
            [
                'name' => 'DoorDash',
                'description' => 'DoorDash delivery orders',
                'category' => 'online',
                'sort_order' => 5
            ],
            [
                'name' => 'EZ Catering',
                'description' => 'EZ Catering orders',
                'category' => 'online',
                'sort_order' => 6
            ],
            [
                'name' => 'Relish',
                'description' => 'Relish platform orders',
                'category' => 'online',
                'sort_order' => 7
            ],
            [
                'name' => 'Grubhub',
                'description' => 'Grubhub delivery orders',
                'category' => 'online',
                'sort_order' => 8
            ]
        ];

        foreach ($revenueTypes as $type) {
            RevenueIncomeType::create($type);
        }
    }
}
