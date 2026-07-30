<?php

namespace App\Console\Commands;

use App\Models\ChartOfAccount;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Renumber accounts so each code sits inside its parent's code range (e.g. an
 * insurance account moved under Insurance Total 6900 becomes 69xx). The hierarchy
 * itself is unchanged — this only relabels codes to match the stored parent.
 *
 * Rules:
 *  - Codes already inside their parent's range are kept (minimal churn).
 *  - Wired-in codes are never moved (merchant-fee + delivery-platform logic).
 *  - A code that can't fit (its parent's block is full) is left as-is and reported.
 *  - account_code is globally unique, so --apply writes in two phases (temp codes
 *    first) to avoid transient collisions.
 *
 * Dry run by default; pass --apply to write.
 */
class RenumberChartOfAccounts extends Command
{
    protected $signature = 'coa:renumber {--apply : Actually write the new codes (default is a dry run)}';

    protected $description = 'Renumber accounts so their code matches their parent (dry run by default)';

    /** Codes wired into logic (merchant fees, delivery platforms) — never move. */
    private array $protected = ['6000', '6100', '6451', '6452', '6453'];

    private array $newCode = [];   // id => new code
    private array $claimed = [];   // code => true (globally used)
    private array $warnings = [];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $all = ChartOfAccount::withoutGlobalScopes()->get(['id', 'account_code', 'account_name', 'account_type', 'parent_account_id']);
        $byId = $all->keyBy('id');
        $this->childrenByParent = $all->groupBy('parent_account_id');
        $this->claimed = $all->pluck('account_code')->mapWithKeys(fn ($c) => [(string) $c => true])->all();

        // Type roots keep their code; assign each subtree top-down.
        $roots = $all->filter(fn ($a) => ! $a->parent_account_id || ! $byId->has($a->parent_account_id))
            ->sortBy(fn ($a) => (int) $a->account_code);

        foreach ($roots as $root) {
            $this->newCode[$root->id] = (string) $root->account_code;
            $this->assign($root, (string) $root->account_code);
        }

        // Collect actual changes.
        $changes = [];
        foreach ($all as $a) {
            $nc = $this->newCode[$a->id] ?? (string) $a->account_code;
            if ($nc !== (string) $a->account_code) {
                $changes[] = [$a, $nc];
            }
        }

        $this->info('Renumbering ('.count($changes).' change(s)):');
        foreach ($changes as [$a, $nc]) {
            $parent = $a->parent_account_id ? ($byId[$a->parent_account_id] ?? null) : null;
            $under = $parent ? ' [under '.($this->newCode[$parent->id] ?? $parent->account_code).' '.$parent->account_name.']' : '';
            $this->line("  {$a->account_code} → {$nc}  {$a->account_name}{$under}");
        }

        if ($this->warnings) {
            $this->newLine();
            $this->warn('Could not renumber (parent block full — left as-is):');
            foreach ($this->warnings as $w) {
                $this->line('  '.$w);
            }
        }

        if (! $apply) {
            $this->newLine();
            $this->info('Dry run only. Re-run with --apply to write.');

            return self::SUCCESS;
        }

        // Two-phase write to dodge the unique constraint during the swap.
        DB::transaction(function () use ($changes) {
            foreach ($changes as [$a]) {
                DB::table('chart_of_accounts')->where('id', $a->id)->update(['account_code' => 'T'.$a->id]);
            }
            foreach ($changes as [$a, $nc]) {
                DB::table('chart_of_accounts')->where('id', $a->id)->update(['account_code' => $nc]);
            }
        });

        $this->newLine();
        $this->info('Applied — renumbered '.count($changes).' account(s).');

        return self::SUCCESS;
    }

    private $childrenByParent;

    /** Assign codes to $parent's children (recursively), given the parent's new code. */
    private function assign(ChartOfAccount $parent, string $parentNewCode): void
    {
        $children = $this->childrenByParent->get($parent->id, collect())
            ->sortBy(fn ($c) => (int) $c->account_code);

        $range = ChartOfAccount::childCodeRangeForParent($parentNewCode);

        foreach ($children as $child) {
            $current = (string) $child->account_code;
            $nc = $current;

            if (in_array($current, $this->protected, true)) {
                // Keep wired-in codes exactly.
            } elseif ($range === null) {
                $this->warnings[] = "{$current} {$child->account_name}: parent {$parentNewCode} can't hold sub-accounts";
            } elseif ((int) $current >= $range[0] && (int) $current <= $range[1]) {
                // Already inside the parent's range — keep it.
            } else {
                $free = $this->nextFree($parentNewCode, $range);
                if ($free === null) {
                    $this->warnings[] = "{$current} {$child->account_name}: block under {$parentNewCode} is full";
                } else {
                    $this->claimed[$free] = true;
                    $nc = $free;
                }
            }

            $this->newCode[$child->id] = $nc;
            $this->assign($child, $nc);
        }
    }

    /** Next unused code in a parent's range, preferring the natural step. */
    private function nextFree(string $parentCode, array $range): ?string
    {
        [$min, $max] = $range;
        $base = (int) $parentCode;
        $step = $base % 1000 === 0 ? 100 : ($base % 100 === 0 ? 10 : 1);

        for ($c = $base + $step; $c <= $max; $c += $step) {
            if (! isset($this->claimed[(string) $c])) {
                return (string) $c;
            }
        }
        for ($c = $min; $c <= $max; $c++) {
            if (! isset($this->claimed[(string) $c])) {
                return (string) $c;
            }
        }

        return null;
    }
}
