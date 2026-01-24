<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $accounts = [
            // ============================================
            // ASSETS (1000-1999)
            // ============================================
            ['account_code' => '1000', 'account_name' => 'Assets All', 'account_type' => 'Assets', 'is_system_account' => true],
            ['account_code' => '1010', 'account_name' => 'Cash - Operating Account', 'account_type' => 'Assets', 'is_system_account' => true],
            ['account_code' => '1100', 'account_name' => 'Petty Cash', 'account_type' => 'Assets', 'is_system_account' => true],
            ['account_code' => '1200', 'account_name' => 'Accounts Receivable', 'account_type' => 'Assets', 'is_system_account' => true],
            ['account_code' => '1300', 'account_name' => 'Inventory - Food', 'account_type' => 'Assets', 'is_system_account' => true],
            ['account_code' => '1310', 'account_name' => 'Inventory - Beverages', 'account_type' => 'Assets', 'is_system_account' => true],
            ['account_code' => '1400', 'account_name' => 'Prepaid Expenses', 'account_type' => 'Assets', 'is_system_account' => true],
            ['account_code' => '1500', 'account_name' => 'Equipment', 'account_type' => 'Assets', 'is_system_account' => true],
            ['account_code' => '1510', 'account_name' => 'Accumulated Depreciation - Equipment', 'account_type' => 'Assets', 'is_system_account' => true],
            ['account_code' => '1600', 'account_name' => 'Furniture & Fixtures', 'account_type' => 'Assets', 'is_system_account' => true],
            ['account_code' => '1610', 'account_name' => 'Accumulated Depreciation - Furniture', 'account_type' => 'Assets', 'is_system_account' => true],
            
            // ============================================
            // LIABILITIES (2000-2999)
            // ============================================
            ['account_code' => '2000', 'account_name' => 'Liabilities All', 'account_type' => 'Liability', 'is_system_account' => true],
            ['account_code' => '2010', 'account_name' => 'Accounts Payable', 'account_type' => 'Liability', 'is_system_account' => true],
            ['account_code' => '2100', 'account_name' => 'Accrued Expenses', 'account_type' => 'Liability', 'is_system_account' => true],
            ['account_code' => '2400', 'account_name' => 'Short-term Loans', 'account_type' => 'Liability', 'is_system_account' => true],
            ['account_code' => '2500', 'account_name' => 'Credit Card Payable', 'account_type' => 'Liability', 'is_system_account' => true],
            
            // ============================================
            // TAXES (3000-3999)
            // ============================================
            ['account_code' => '3000', 'account_name' => 'Taxes All', 'account_type' => 'Taxes', 'is_system_account' => true],
            ['account_code' => '3100', 'account_name' => 'Sales Tax Payable', 'account_type' => 'Taxes', 'is_system_account' => true],
            ['account_code' => '3200', 'account_name' => 'Payroll Taxes Payable', 'account_type' => 'Taxes', 'is_system_account' => true],
            
            // ============================================
            // REVENUE (4000-4999)
            // ============================================
            ['account_code' => '4000', 'account_name' => 'Revenues All', 'account_type' => 'Revenue', 'is_system_account' => true],
            ['account_code' => '4100', 'account_name' => 'Revenue - Food Sales', 'account_type' => 'Revenue', 'is_system_account' => true],
            ['account_code' => '4200', 'account_name' => 'Revenue - Beverage Sales', 'account_type' => 'Revenue', 'is_system_account' => true],
            ['account_code' => '4300', 'account_name' => 'Revenue - Third Party (Grubhub/Uber)', 'account_type' => 'Revenue', 'is_system_account' => true],
            ['account_code' => '4400', 'account_name' => 'Other Income', 'account_type' => 'Revenue', 'is_system_account' => true],
            
            // ============================================
            // COST OF GOODS SOLD (5000-5999)
            // ============================================
            ['account_code' => '5000', 'account_name' => 'COGS All', 'account_type' => 'COGS', 'is_system_account' => true],
            ['account_code' => '5100', 'account_name' => 'COGS - Food Purchases', 'account_type' => 'COGS', 'is_system_account' => true],
            ['account_code' => '5200', 'account_name' => 'COGS - Beverage Purchases', 'account_type' => 'COGS', 'is_system_account' => true],
            ['account_code' => '5300', 'account_name' => 'COGS - Packaging Supplies', 'account_type' => 'COGS', 'is_system_account' => true],
            
            // ============================================
            // OPERATING EXPENSES (6000-6999)
            // ============================================
            ['account_code' => '6000', 'account_name' => 'Expenses All', 'account_type' => 'Expense', 'is_system_account' => true],
            ['account_code' => '6100', 'account_name' => 'Merchant Processing Fees', 'account_type' => 'Expense', 'is_system_account' => true],
            ['account_code' => '6200', 'account_name' => 'Marketing Fees (Grubhub)', 'account_type' => 'Expense', 'is_system_account' => true],
            ['account_code' => '6300', 'account_name' => 'Delivery Service Fees', 'account_type' => 'Expense', 'is_system_account' => true],
            ['account_code' => '6400', 'account_name' => 'Utilities - Electric', 'account_type' => 'Expense', 'is_system_account' => true],
            ['account_code' => '6410', 'account_name' => 'Utilities - Water', 'account_type' => 'Expense', 'is_system_account' => true],
            ['account_code' => '6420', 'account_name' => 'Utilities - Gas', 'account_type' => 'Expense', 'is_system_account' => true],
            ['account_code' => '6430', 'account_name' => 'Utilities - Internet', 'account_type' => 'Expense', 'is_system_account' => true],
            ['account_code' => '6500', 'account_name' => 'Rent', 'account_type' => 'Expense', 'is_system_account' => true],
            ['account_code' => '6600', 'account_name' => 'Payroll', 'account_type' => 'Expense', 'is_system_account' => true],
            ['account_code' => '6610', 'account_name' => 'Payroll Taxes', 'account_type' => 'Expense', 'is_system_account' => true],
            ['account_code' => '6700', 'account_name' => 'Supplies - Paper Goods', 'account_type' => 'Expense', 'is_system_account' => true],
            ['account_code' => '6710', 'account_name' => 'Supplies - Cleaning', 'account_type' => 'Expense', 'is_system_account' => true],
            ['account_code' => '6800', 'account_name' => 'Maintenance & Repairs', 'account_type' => 'Expense', 'is_system_account' => true],
            ['account_code' => '6900', 'account_name' => 'Insurance', 'account_type' => 'Expense', 'is_system_account' => true],
            ['account_code' => '6950', 'account_name' => 'Professional Services', 'account_type' => 'Expense', 'is_system_account' => true],
            ['account_code' => '6960', 'account_name' => 'Legal & Accounting', 'account_type' => 'Expense', 'is_system_account' => true],
            ['account_code' => '6970', 'account_name' => 'Marketing & Advertising', 'account_type' => 'Expense', 'is_system_account' => true],

            // ============================================
            // ADJUSTMENTS (7000-7999)
            // ============================================
            ['account_code' => '7000', 'account_name' => 'Adjustments All', 'account_type' => 'Adjustments', 'is_system_account' => true],
            ['account_code' => '7100', 'account_name' => 'Adjustments - Overrings/Returns', 'account_type' => 'Adjustments', 'is_system_account' => true],

            // ============================================
            // EQUITY (8000-8999)
            // ============================================
            ['account_code' => '8000', 'account_name' => 'Equity All', 'account_type' => 'Equity', 'is_system_account' => true],
            ['account_code' => '8100', 'account_name' => 'Owner\'s Equity', 'account_type' => 'Equity', 'is_system_account' => true],
            ['account_code' => '8200', 'account_name' => 'Retained Earnings', 'account_type' => 'Equity', 'is_system_account' => true],
            ['account_code' => '8300', 'account_name' => 'Current Year Earnings', 'account_type' => 'Equity', 'is_system_account' => true],
        ];

        $rows = array_map(function (array $account) use ($now) {
            return array_merge($account, [
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }, $accounts);

        // Safe to run multiple times:
        // - Inserts missing accounts
        // - Updates existing accounts by account_code (keeps same IDs, so references remain intact)
        DB::table('chart_of_accounts')->upsert(
            $rows,
            ['account_code'],
            ['account_name', 'account_type', 'is_system_account', 'is_active', 'updated_at']
        );
    }
}
