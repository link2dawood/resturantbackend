<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            // Revenue Accounts
            ['account_code' => '4000', 'account_name' => 'Revenue - Food Sales', 'account_type' => 'Revenue', 'is_system_account' => true],
            ['account_code' => '4100', 'account_name' => 'Revenue - Beverage Sales', 'account_type' => 'Revenue', 'is_system_account' => true],
            ['account_code' => '4200', 'account_name' => 'Revenue - Third Party (Grubhub/Uber)', 'account_type' => 'Revenue', 'is_system_account' => true],
            
            // COGS Accounts
            ['account_code' => '5000', 'account_name' => 'COGS - Food Purchases', 'account_type' => 'COGS', 'is_system_account' => true],
            ['account_code' => '5100', 'account_name' => 'COGS - Beverage Purchases', 'account_type' => 'COGS', 'is_system_account' => true],
            ['account_code' => '5200', 'account_name' => 'COGS - Packaging Supplies', 'account_type' => 'COGS', 'is_system_account' => true],
            
            // Expense Accounts
            ['account_code' => '6000', 'account_name' => 'Merchant Processing Fees', 'account_type' => 'Expense', 'is_system_account' => true],
            ['account_code' => '6100', 'account_name' => 'Marketing Fees (Grubhub)', 'account_type' => 'Expense', 'is_system_account' => true],
            ['account_code' => '6200', 'account_name' => 'Delivery Service Fees', 'account_type' => 'Expense', 'is_system_account' => true],
            ['account_code' => '6300', 'account_name' => 'Utilities - Electric', 'account_type' => 'Expense', 'is_system_account' => true],
            ['account_code' => '6310', 'account_name' => 'Utilities - Water', 'account_type' => 'Expense', 'is_system_account' => true],
            ['account_code' => '6320', 'account_name' => 'Utilities - Gas', 'account_type' => 'Expense', 'is_system_account' => true],
            ['account_code' => '6330', 'account_name' => 'Utilities - Internet', 'account_type' => 'Expense', 'is_system_account' => true],
            ['account_code' => '6400', 'account_name' => 'Rent', 'account_type' => 'Expense', 'is_system_account' => true],
            ['account_code' => '6500', 'account_name' => 'Payroll', 'account_type' => 'Expense', 'is_system_account' => true],
            ['account_code' => '6600', 'account_name' => 'Supplies - Paper Goods', 'account_type' => 'Expense', 'is_system_account' => true],
            ['account_code' => '6700', 'account_name' => 'Maintenance & Repairs', 'account_type' => 'Expense', 'is_system_account' => true],
            ['account_code' => '6800', 'account_name' => 'Insurance', 'account_type' => 'Expense', 'is_system_account' => true],
            ['account_code' => '6900', 'account_name' => 'Professional Services', 'account_type' => 'Expense', 'is_system_account' => true],
        ];

        foreach ($accounts as $account) {
            DB::table('chart_of_accounts')->insert(array_merge($account, [
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
