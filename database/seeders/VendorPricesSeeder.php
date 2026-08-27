<?php

namespace Database\Seeders;

use App\Models\InventoryItem;
use App\Models\User;
use App\Models\VendorPrice;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Phase 5 — gives every seeded item/vendor mapping a prior price point.
 *
 * InventoryItemsSeeder already writes today's price for each mapping (through
 * ItemVendorMapper, which mirrors into vendor_prices). Without an earlier row
 * there is nothing to compare against, so the comparison screen would show no
 * movement at all. This backdates a second quote 30 days earlier, a few percent
 * either side, so the up/down arrows have real data behind them.
 *
 * Idempotent: skips any (item, vendor) that already has more than one quote.
 */
class VendorPricesSeeder extends Seeder
{
    /** name => percentage the price moved since the prior quote */
    private const MOVES = [
        'Steak' => 6.0,        // beef up
        'Chicken' => -3.5,     // chicken down
        'Gyro' => 0.0,         // held
        '8" Bread' => 2.0,
        '10" Bread' => 2.0,
        'Pita' => -1.5,
        'Provolone' => 4.5,
        'Onions' => -8.0,      // produce swing
        'Frying Oil' => 11.0,  // oil spike
        'Coke' => 0.0,
    ];

    public function run(): void
    {
        $enteredBy = User::where('role', 'admin')->value('id');
        $priorDate = Carbon::now()->subDays(30)->toDateString();
        $written = 0;

        $items = InventoryItem::with('vendors')->get();

        foreach ($items as $item) {
            $movePct = self::MOVES[$item->name] ?? null;

            if ($movePct === null) {
                continue;
            }

            foreach ($item->vendors as $vendor) {
                $currentPrice = (float) $vendor->pivot->current_price;

                if ($currentPrice <= 0) {
                    continue;
                }

                $alreadyHasHistory = VendorPrice::where('inventory_item_id', $item->id)
                    ->where('vendor_id', $vendor->id)
                    ->count() > 1;

                if ($alreadyHasHistory) {
                    continue;
                }

                // Work backwards: the prior price that moved by $movePct lands on
                // today's price.
                $priorPrice = $movePct == 0.0
                    ? $currentPrice
                    : round($currentPrice / (1 + ($movePct / 100)), 2);

                VendorPrice::create([
                    'vendor_id' => $vendor->id,
                    'inventory_item_id' => $item->id,
                    'price' => $priorPrice,
                    'price_unit' => $item->purchase_unit,
                    'effective_date' => $priorDate,
                    'entered_by' => $enteredBy,
                ]);
                $written++;
            }
        }

        $this->command?->info("Seeded {$written} prior vendor price(s) for comparison.");
    }
}
