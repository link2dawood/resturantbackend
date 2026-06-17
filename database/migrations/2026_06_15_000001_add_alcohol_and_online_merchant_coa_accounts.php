<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * CoA hierarchy improvements: add the Alcohol COGS category (5400) and the
 * Online Merchant Expenses block (6450 total + DoorDash/GrubHub/Uber Eats/
 * EasyCatering sub-accounts) to existing databases. Insert-if-missing so a
 * store's existing/renamed accounts are never overwritten.
 */
return new class extends Migration
{
    public function up(): void
    {
        $accounts = [
            ['5400', 'Alcohol', 'COGS'],
            ['6450', 'Online Merchant Expenses', 'Expense'],
            ['6451', 'DoorDash', 'Expense'],
            ['6452', 'GrubHub', 'Expense'],
            ['6453', 'Uber Eats', 'Expense'],
            ['6454', 'EasyCatering', 'Expense'],
        ];

        $now = now();

        foreach ($accounts as [$code, $name, $type]) {
            $exists = DB::table('chart_of_accounts')->where('account_code', $code)->exists();

            if (! $exists) {
                DB::table('chart_of_accounts')->insert([
                    'account_code' => $code,
                    'account_name' => $name,
                    'account_type' => $type,
                    'is_system_account' => true,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Only remove the rows we may have added, and only if unused.
        DB::table('chart_of_accounts')
            ->whereIn('account_code', ['5400', '6450', '6451', '6452', '6453', '6454'])
            ->where('is_system_account', true)
            ->delete();
    }
};
