<?php

namespace App\Console\Commands;

use App\Models\Vendor;
use App\Models\VendorAlias;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Merge exact-duplicate-name vendors (case-insensitive) into a single keeper,
 * repointing their transactions first so nothing is orphaned. Created to clean
 * up the duplicate rows left by the pre-fix vendor-create crash. Different
 * spellings (e.g. "Sam's Club" vs "Sams Club") are intentionally NOT merged —
 * that needs a human to pick the canonical name.
 *
 * Dry run by default; pass --apply to actually merge.
 */
class DedupeVendors extends Command
{
    protected $signature = 'vendors:dedupe {--apply : Actually merge (default is a dry run)}';

    protected $description = 'Merge exact-duplicate-name vendors, repointing transactions to one keeper';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $groups = Vendor::orderBy('id')->get()
            ->groupBy(fn ($v) => mb_strtolower(trim($v->vendor_name)))
            ->filter(fn ($group) => $group->count() > 1);

        if ($groups->isEmpty()) {
            $this->info('No exact-duplicate vendor names found.');

            return self::SUCCESS;
        }

        $mergedCount = 0;

        foreach ($groups as $name => $vendors) {
            // Keeper = the vendor that owns the manual alias for this name, else
            // the lowest id (the earliest, most-referenced one).
            $aliasOwnerId = VendorAlias::whereIn('vendor_id', $vendors->pluck('id'))
                ->whereRaw('LOWER(alias) = ?', [$name])
                ->where('source', 'manual')
                ->value('vendor_id');

            $keeper = ($aliasOwnerId && $vendors->firstWhere('id', $aliasOwnerId))
                ? $vendors->firstWhere('id', $aliasOwnerId)
                : $vendors->first();

            $dupes = $vendors->where('id', '!=', $keeper->id);
            $dupeIds = $dupes->pluck('id')->all();

            $this->line(sprintf(
                '"%s": keep #%d, merge #%s',
                $keeper->vendor_name,
                $keeper->id,
                implode(', #', $dupeIds)
            ));

            if ($apply) {
                DB::transaction(function () use ($keeper, $dupeIds) {
                    // Repoint the two nullable references so no transaction is lost.
                    DB::table('expense_transactions')->whereIn('vendor_id', $dupeIds)->update(['vendor_id' => $keeper->id]);
                    DB::table('transaction_mapping_rules')->whereIn('vendor_id', $dupeIds)->update(['vendor_id' => $keeper->id]);

                    // Deleting the dupes cascade-removes their aliases/store links.
                    Vendor::whereIn('id', $dupeIds)->delete();

                    // Make sure the keeper still has its manual alias.
                    VendorAlias::firstOrCreate(
                        ['alias' => $keeper->vendor_name, 'source' => 'manual'],
                        ['vendor_id' => $keeper->id]
                    );
                });

                $mergedCount += count($dupeIds);
            }
        }

        $this->newLine();
        $this->info($apply
            ? "Done — merged {$mergedCount} duplicate vendor row(s)."
            : 'Dry run only. Re-run with --apply to merge.');

        return self::SUCCESS;
    }
}
