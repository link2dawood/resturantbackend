<?php

namespace App\Console\Commands;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Store;
use App\Models\StoreInventoryTarget;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Phase 5 Part 1 — bulk stock targets.
 *
 * Order suggestions are "target minus counted", so an item with no target can
 * never suggest anything. Setting 123 of them by hand is the slow path; this
 * lays down a baseline in one command and leaves the manager to tune the
 * handful that differ.
 *
 * Dry run is the default. Nothing is written without --commit.
 */
class SetInventoryTargets extends Command
{
    protected $signature = 'inventory:set-targets
        {--store= : Store id; omit to apply to every store}
        {--target=10 : Target stock level, in the item purchase unit}
        {--min=3 : Reorder point, in the item purchase unit}
        {--category= : Limit to one category name, e.g. Meats}
        {--overwrite : Also change targets that are already set}
        {--commit : Actually write. Without this the command only previews.}';

    protected $description = 'Set a baseline stock target on inventory items that have none';

    public function handle(): int
    {
        $target = (float) $this->option('target');
        $min = $this->option('min') === null || $this->option('min') === ''
            ? null
            : (float) $this->option('min');

        if ($target < 0 || ($min !== null && $min < 0)) {
            $this->error('Target and min must not be negative.');

            return self::FAILURE;
        }

        if ($min !== null && $min > $target) {
            $this->warn("The reorder point ({$min}) is above the target ({$target}). Every item will read as low stock.");
        }

        $stores = $this->option('store')
            ? Store::where('id', (int) $this->option('store'))->get()
            : Store::all();

        if ($stores->isEmpty()) {
            $this->error('No matching store.');

            return self::FAILURE;
        }

        $category = null;

        if ($this->option('category')) {
            $category = InventoryCategory::whereRaw('LOWER(name) = ?', [mb_strtolower($this->option('category'))])->first();

            if (! $category) {
                $this->error('No category named '.$this->option('category').'. Available: '
                    .InventoryCategory::ordered()->pluck('name')->implode(', '));

                return self::FAILURE;
            }
        }

        $overwrite = (bool) $this->option('overwrite');
        $commit = (bool) $this->option('commit');

        $this->line('');
        $this->info('Target: '.$target.'   Reorder point: '.($min ?? 'none'));
        $this->info('Stores: '.$stores->pluck('store_info')->implode(', '));
        $this->info('Category: '.($category?->name ?? 'all'));
        $this->info('Mode:   '.($commit ? 'COMMIT (will write)' : 'DRY RUN (nothing will be written)'));
        $this->line('');

        $rows = [];
        $toWrite = 0;
        $toSkip = 0;

        foreach ($stores as $store) {
            $query = InventoryItem::with('inventoryCategory')
                ->where('store_id', $store->id)
                ->where('is_active', true);

            if ($category) {
                $query->where('inventory_category_id', $category->id);
            }

            $items = $query->get();
            $existing = StoreInventoryTarget::where('store_id', $store->id)
                ->pluck('target_stock_level', 'inventory_item_id');

            $storeWrite = 0;
            $storeSkip = 0;

            foreach ($items as $item) {
                $has = $existing->has($item->id);

                if ($has && ! $overwrite) {
                    $storeSkip++;

                    continue;
                }

                $storeWrite++;

                if ($commit) {
                    StoreInventoryTarget::updateOrCreate(
                        ['store_id' => $store->id, 'inventory_item_id' => $item->id],
                        ['target_stock_level' => $target, 'min_stock_level' => $min]
                    );
                }
            }

            $rows[] = [
                $store->id,
                $store->store_info,
                $items->count(),
                $storeWrite,
                $storeSkip,
            ];

            $toWrite += $storeWrite;
            $toSkip += $storeSkip;
        }

        $this->table(['Store', 'Name', 'Active items', $commit ? 'Set' : 'Would set', 'Left alone'], $rows);

        if (! $commit) {
            $this->line('');
            $this->warn("Dry run. {$toWrite} target(s) would be set, {$toSkip} left alone.");
            $this->line('Re-run with --commit to apply.');

            return self::SUCCESS;
        }

        $this->line('');
        $this->info("Done. {$toWrite} target(s) set, {$toSkip} left alone.");
        $this->line('Adjust individual items at /stores/{id}/inventory-targets.');

        return self::SUCCESS;
    }
}
