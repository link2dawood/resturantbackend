<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VendorsSeeder extends Seeder
{
    public function run(): void
    {
        $vendors = [
            ['vendor_name' => 'Sam\'s Club', 'vendor_identifier' => 'SAMSCLUB', 'vendor_type' => 'Food'],
            ['vendor_name' => 'Sysco Foods', 'vendor_identifier' => 'SYSCO', 'vendor_type' => 'Food'],
            ['vendor_name' => 'Coca-Cola', 'vendor_identifier' => 'COCA-COLA', 'vendor_type' => 'Beverage'],
            ['vendor_name' => 'Spectrum', 'vendor_identifier' => 'SPECTRUM', 'vendor_type' => 'Utilities'],
            ['vendor_name' => 'AT&T', 'vendor_identifier' => 'ATT', 'vendor_type' => 'Utilities'],
            ['vendor_name' => 'Grubhub', 'vendor_identifier' => 'GRUBHUB', 'vendor_type' => 'Services'],
            ['vendor_name' => 'Square', 'vendor_identifier' => 'SQ *SQUARE', 'vendor_type' => 'Services'],
            ['vendor_name' => 'Travelers Insurance', 'vendor_identifier' => 'TRAVELERS', 'vendor_type' => 'Services'],
        ];

        // Get COA IDs by account type
        $coaIds = [];
        $coaIds['Food'] = DB::table('chart_of_accounts')->where('account_code', '5100')->value('id'); // COGS - Food Purchases
        $coaIds['Beverage'] = DB::table('chart_of_accounts')->where('account_code', '5200')->value('id'); // COGS - Beverage Purchases
        $coaIds['Services'] = DB::table('chart_of_accounts')->where('account_code', '6100')->value('id'); // Merchant Processing Fees
        $coaIds['Utilities'] = DB::table('chart_of_accounts')->where('account_code', '6400')->value('id'); // Utilities - Electric
        $coaIds['Grubhub'] = DB::table('chart_of_accounts')->where('account_code', '6200')->value('id'); // Marketing Fees (Grubhub)

        foreach ($vendors as $vendor) {
            $defaultCoaId = null;
            if ($vendor['vendor_name'] === 'Grubhub') {
                $defaultCoaId = $coaIds['Grubhub'];
            } else {
                $defaultCoaId = $coaIds[$vendor['vendor_type']] ?? null;
            }

            $vendorId = DB::table('vendors')->insertGetId(array_merge($vendor, [
                'default_coa_id' => $defaultCoaId,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));

            // Create initial alias from vendor name
            DB::table('vendor_aliases')->insert([
                'vendor_id' => $vendorId,
                'alias' => $vendor['vendor_name'],
                'source' => 'manual',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Add vendor_identifier as alias if different from name
            if ($vendor['vendor_identifier'] && strtoupper($vendor['vendor_identifier']) !== strtoupper($vendor['vendor_name'])) {
                DB::table('vendor_aliases')->insert([
                    'vendor_id' => $vendorId,
                    'alias' => $vendor['vendor_identifier'],
                    'source' => 'manual',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
