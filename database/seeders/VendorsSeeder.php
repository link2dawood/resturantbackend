<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VendorsSeeder extends Seeder
{
    public function run(): void
    {
        // Phase 5.6 supply vendors + retained expense/service vendors. Idempotent
        // (matched by vendor_name) so re-seeding never duplicates.
        $vendors = [
            // Ordering / supply vendors
            ['vendor_name' => 'Lisanti', 'vendor_identifier' => 'LISANTI', 'vendor_type' => 'Food', 'order_method' => 'phone'],
            ['vendor_name' => 'Restaurant Depot', 'vendor_identifier' => 'RESTAURANT DEPOT', 'vendor_type' => 'Food', 'order_method' => 'online'],
            ['vendor_name' => 'Sam\'s Club', 'vendor_identifier' => 'SAMSCLUB', 'vendor_type' => 'Food', 'order_method' => 'online'],
            ['vendor_name' => 'Coca-Cola', 'vendor_identifier' => 'COCA-COLA', 'vendor_type' => 'Beverage', 'order_method' => 'online'],
            ['vendor_name' => 'Walmart', 'vendor_identifier' => 'WALMART', 'vendor_type' => 'Supplies', 'order_method' => 'in_person'],
            ['vendor_name' => 'HEB', 'vendor_identifier' => 'HEB', 'vendor_type' => 'Food', 'order_method' => 'in_person'],
            // Expense / service vendors (retained)
            ['vendor_name' => 'Spectrum', 'vendor_identifier' => 'SPECTRUM', 'vendor_type' => 'Utilities'],
            ['vendor_name' => 'AT&T', 'vendor_identifier' => 'ATT', 'vendor_type' => 'Utilities'],
            ['vendor_name' => 'Grubhub', 'vendor_identifier' => 'GRUBHUB', 'vendor_type' => 'Services'],
            ['vendor_name' => 'Square', 'vendor_identifier' => 'SQ *SQUARE', 'vendor_type' => 'Services'],
            ['vendor_name' => 'Travelers Insurance', 'vendor_identifier' => 'TRAVELERS', 'vendor_type' => 'Services'],
        ];

        $coaByType = [
            'Food' => DB::table('chart_of_accounts')->where('account_code', '5100')->value('id'),
            'Beverage' => DB::table('chart_of_accounts')->where('account_code', '5200')->value('id'),
            'Services' => DB::table('chart_of_accounts')->where('account_code', '6100')->value('id'),
            'Utilities' => DB::table('chart_of_accounts')->where('account_code', '6400')->value('id'),
        ];

        foreach ($vendors as $vendor) {
            $coaId = $vendor['vendor_name'] === 'Grubhub'
                ? DB::table('chart_of_accounts')->where('account_code', '6200')->value('id')
                : ($coaByType[$vendor['vendor_type']] ?? null);

            // Match an existing vendor by name OR by a known alias (identifier),
            // so we never create a duplicate for a vendor the client already has.
            $existingId = DB::table('vendors')->where('vendor_name', $vendor['vendor_name'])->value('id')
                ?? DB::table('vendor_aliases')
                    ->whereIn('alias', [$vendor['vendor_name'], $vendor['vendor_identifier']])
                    ->value('vendor_id');

            if ($existingId) {
                DB::table('vendors')->where('id', $existingId)->update(array_filter([
                    'vendor_type' => $vendor['vendor_type'],
                    'is_active' => true,
                    // Only fill the method in; never blank one an admin has set.
                    'order_method' => DB::table('vendors')->where('id', $existingId)->value('order_method')
                        ?: ($vendor['order_method'] ?? null),
                    'updated_at' => now(),
                ], fn ($v) => $v !== null));

                continue;
            }

            $vendorId = DB::table('vendors')->insertGetId(array_merge($vendor, [
                'default_coa_id' => $coaId,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));

            foreach (array_unique([$vendor['vendor_name'], $vendor['vendor_identifier']]) as $alias) {
                // insertOrIgnore: skip an alias that already exists (unique alias+source).
                DB::table('vendor_aliases')->insertOrIgnore([
                    'vendor_id' => $vendorId, 'alias' => $alias, 'source' => 'manual',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }

        // Retire outdated vendors (kept, not deleted, to preserve any references).
        DB::table('vendors')
            ->whereIn('vendor_name', ['Sysco Foods', 'Sysco', 'Cisco', 'K&M Distributors', 'Nogales Produce'])
            ->update(['is_active' => false, 'updated_at' => now()]);
    }
}
