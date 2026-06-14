<?php

namespace Database\Seeders;

use App\Services\CoaTemplateService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // Single source of truth for the Fann's Philly standard chart.
        $accounts = app(CoaTemplateService::class)->defaultAccounts();

        $rows = array_map(function (array $account) use ($now) {
            return array_merge($account, [
                'is_system_account' => true,
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
