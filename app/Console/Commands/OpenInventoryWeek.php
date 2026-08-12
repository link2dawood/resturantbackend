<?php

namespace App\Console\Commands;

use App\Models\InventoryItem;
use App\Models\InventoryStock;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Phase 5: open the weekly inventory-count rows. Runs every Monday (scheduled in
 * routes/console.php); creates one inventory_stock row per active item per
 * store for the week, seeding starting_stock from the prior week's actual
 * ending count. Idempotent — existing rows for the week are left untouched.
 */
class OpenInventoryWeek extends Command
{
    protected $signature = 'inventory:open-week {--week= : Monday date (Y-m-d); defaults to the current week}';

    protected $description = 'Open the weekly inventory-count rows for each store (idempotent)';

    public function handle(): int
    {
        $week = $this->option('week')
            ? Carbon::parse($this->option('week'))->startOfDay()
            : Carbon::now()->startOfWeek(Carbon::MONDAY);

        $weekStr = $week->toDateString();
        $prevStr = $week->copy()->subWeek()->toDateString();

        $created = 0;
        InventoryItem::where('is_active', true)->chunkById(200, function ($items) use ($weekStr, $prevStr, &$created) {
            foreach ($items as $item) {
                $exists = InventoryStock::where('inventory_item_id', $item->id)
                    ->whereDate('week_start_date', $weekStr)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $priorEnding = InventoryStock::where('inventory_item_id', $item->id)
                    ->whereDate('week_start_date', $prevStr)
                    ->value('actual_ending_stock');

                InventoryStock::create([
                    'inventory_item_id' => $item->id,
                    'store_id' => $item->store_id,
                    'week_start_date' => $weekStr,
                    'starting_stock' => $priorEnding ?? 0,
                    'actual_ending_stock' => null,
                    'status' => 'draft',
                ]);
                $created++;
            }
        });

        $this->info("Opened inventory week {$weekStr}: {$created} item row(s) created.");

        return self::SUCCESS;
    }
}
