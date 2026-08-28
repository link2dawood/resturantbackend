<?php

namespace App\Console\Commands;

use App\Models\Store;
use App\Services\Inventory\ClientInventoryImporter;
use App\Services\Inventory\ClientInventoryImporterOptions;
use Illuminate\Console\Command;
use RuntimeException;

/**
 * Phase 5 Part 1 Task 15 — one-time import of the client's real order guide.
 *
 * Dry run is the DEFAULT. Nothing is written unless --commit is passed, so a
 * mistyped path or the wrong store cannot quietly rewrite the item master.
 */
class ImportInventoryFromExcel extends Command
{
    protected $signature = 'inventory:import-from-excel
        {file : Path to the client spreadsheet (.xlsx, .xls, .csv or .html)}
        {--store= : Store id to import into; defaults to the only store when there is one}
        {--commit : Actually write. Without this the command only previews.}
        {--no-new-vendors : Skip rows whose vendor does not already exist}
        {--no-new-categories : Skip rows whose category does not already exist}
        {--limit=20 : How many preview rows to print}';

    protected $description = 'Import vendors, categories, items, mappings and prices from the client spreadsheet';

    public function handle(ClientInventoryImporter $importer): int
    {
        $file = $this->argument('file');

        try {
            $store = $this->resolveStore();
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->line('');
        $this->info('Store:  '.($store->store_info ?? 'Store #'.$store->id));
        $this->info('File:   '.$file);
        $this->info('Mode:   '.($this->option('commit') ? 'COMMIT (will write)' : 'DRY RUN (nothing will be written)'));
        $this->line('');

        try {
            $plan = $importer->plan($file, $store);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->printPlan($plan);

        if (! $this->option('commit')) {
            $this->line('');
            $this->warn('Dry run only. Nothing was written.');
            $this->line('Re-run with --commit to apply this plan.');

            return self::SUCCESS;
        }

        if ($this->input->isInteractive()
            && ! $this->confirm('Apply this import to '.($store->store_info ?? 'this store').'?', false)) {
            $this->warn('Cancelled. Nothing was written.');

            return self::SUCCESS;
        }

        $options = new ClientInventoryImporterOptions(
            createVendors: ! $this->option('no-new-vendors'),
            createCategories: ! $this->option('no-new-categories'),
        );

        $result = $importer->commit($plan, $options);

        $this->line('');
        $this->info('Import complete.');
        $this->table(['What', 'Count'], collect($result)->map(
            fn ($count, $key) => [str_replace('_', ' ', ucfirst($key)), $count]
        )->values()->all());

        return self::SUCCESS;
    }

    /** @param array<string, mixed> $plan */
    private function printPlan(array $plan): void
    {
        $this->table(['What', 'Count'], [
            ['Rows read', $plan['rows_read']],
            ['Vendors matched to existing', count($plan['vendors_matched'])],
            ['Vendors to create', count($plan['vendors_to_create'])],
            ['Retired vendors remapped', count($plan['vendors_retired'])],
            ['Categories to create', count($plan['categories_to_create'])],
            ['Items to create', count($plan['items_to_create'])],
            ['Items to update', count($plan['items_to_update'])],
            ['Item/vendor mappings', $plan['mappings']],
            ['Items with several vendors', $plan['multi_vendor_items']],
            ['Prices to record', $plan['prices']],
            ['Rows with warnings', count($plan['errors'])],
        ]);

        if (! empty($plan['vendor_contacts'])) {
            $this->line('');
            $this->info('Vendor contact tab found, covering:');
            $this->line('  '.implode(', ', $plan['vendor_contacts']));
        }

        if (! empty($plan['stores_matched']) || ! empty($plan['stores_unmatched'])) {
            $this->line('');
            $this->info('Store list tab found.');

            if (! empty($plan['stores_matched'])) {
                $this->line('  Matched: '.implode(', ', $plan['stores_matched']));
            }

            if (! empty($plan['stores_unmatched'])) {
                $this->warn('  Not found in the system (create these by hand first): '
                    .implode(', ', $plan['stores_unmatched']));
            }
        }

        if (! empty($plan['vendors_retired'])) {
            $this->line('');
            $this->warn('Retired vendors remapped:');
            foreach ($plan['vendors_retired'] as $mapping) {
                $this->line('  '.$mapping);
            }
        }

        if (! empty($plan['vendors_to_create'])) {
            $this->line('');
            $this->warn('New vendors that would be created:');
            $this->line('  '.implode(', ', $plan['vendors_to_create']));
        }

        if (! empty($plan['categories_to_create'])) {
            $this->line('');
            $this->warn('New categories that would be created:');
            $this->line('  '.implode(', ', $plan['categories_to_create']));
        }

        $limit = max(0, (int) $this->option('limit'));

        if ($limit > 0) {
            $this->line('');
            $this->info("First {$limit} rows:");
            $this->table(
                ['Line', 'Action', 'Item', 'Vendor', 'Category', 'Pack', 'Cost', 'Warnings'],
                collect($plan['rows'])->take($limit)->map(fn ($row) => [
                    $row['line'],
                    $row['action'],
                    \Illuminate\Support\Str::limit($row['name'], 26),
                    empty($row['vendors']) ? '—' : implode(' + ', $row['vendors']),
                    \Illuminate\Support\Str::limit($row['category'], 16),
                    rtrim(rtrim(number_format($row['pack_size'], 2, '.', ''), '0'), '.'),
                    $row['cost'] !== null ? '$'.number_format($row['cost'], 2) : '—',
                    empty($row['errors']) ? '' : count($row['errors']),
                ])->all()
            );

            if (count($plan['rows']) > $limit) {
                $this->line('  ... and '.(count($plan['rows']) - $limit).' more rows.');
            }
        }

        if (! empty($plan['errors'])) {
            $this->line('');
            $this->warn('Warnings (these rows still import):');
            foreach (array_slice($plan['errors'], 0, 15) as $error) {
                $this->line('  '.$error);
            }
            if (count($plan['errors']) > 15) {
                $this->line('  ... and '.(count($plan['errors']) - 15).' more.');
            }
        }
    }

    private function resolveStore(): Store
    {
        if ($this->option('store')) {
            $store = Store::find((int) $this->option('store'));

            if (! $store) {
                throw new RuntimeException('No store with id '.$this->option('store').'.');
            }

            return $store;
        }

        $stores = Store::orderBy('id')->get();

        if ($stores->isEmpty()) {
            throw new RuntimeException('There are no stores to import into.');
        }

        if ($stores->count() > 1) {
            throw new RuntimeException(
                'There are '.$stores->count().' stores. Pass --store= to choose one: '
                .$stores->map(fn ($s) => $s->id.'='.$s->store_info)->implode(', ')
            );
        }

        return $stores->first();
    }
}
