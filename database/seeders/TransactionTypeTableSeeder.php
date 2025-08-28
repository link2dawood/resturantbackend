<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TransactionType;


class TransactionTypeTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            'Accounting',
            'Architect',
            'Bank Fees',
            'Car',
            'Credit Card Fees',
            'DiningIn',
            'DoorDash',
            'Equipment',
            'Food Costs',
            'Foodsby',
            'Gift',
            'GrubHub',
            'Insurance',
            'Laundry and Linen',
            'Loan',
            'Management Fee',
            'Marketing',
            'New Store Investment',
            'Office Supplies',
            'Pest_Control',
            'Postage',
            'Postmates',
            'Relish',
            'Rent',
            'Repairs',
            'Square Online',
            'Tax',
            'Technology',
            'Travel and Expense',
            'UBER',
            'Uniforms',
            'Utilities',
        ];

        foreach ($types as $type) {
            TransactionType::create([
                'name' => $type,
                'p_id' => null, // all top-level
            ]);
        }
    }
}
