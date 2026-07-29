<?php

namespace App\Console\Commands;

use App\Models\ChartOfAccount;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One-off cleanup for the Expense chart: create the missing Equipment category,
 * rename the category parents to "… Total", re-file the accounts the number-based
 * backfill miscategorised, and retire the now-empty Delivery Service Fees.
 *
 * Only the codes listed below are touched; anything missing is skipped with a
 * note. Dry run by default; pass --apply to write. Idempotent.
 */
class RecategorizeChartOfAccounts extends Command
{
    protected $signature = 'coa:recategorize {--apply : Actually write the changes (default is a dry run)}';

    protected $description = 'Rename Expense parents to "… Total", re-file miscategorised accounts, tidy the tree';

    /** New category accounts to create if absent: code => name (all Expense, top-level). */
    private array $create = [
        '6550' => 'Equipment Total',
    ];

    /** account_code => new account_name */
    private array $renames = [
        '6050' => 'Banking Fees Total',
        '6150' => 'Donations Total',
        '6200' => 'Marketing Fees Total',
        '6400' => 'Utilities Total',
        '6450' => 'Online Merchant Expenses Total',
        '6500' => 'Rent Total',
        '6600' => 'Payroll Total',
        '6700' => 'Supplies Total',
        '6800' => 'Maintenance & Repairs Total',
        '6900' => 'Insurance Total',
        '6910' => 'Travel and Expense Total',
        '6950' => 'Professional Services Total',
        '6960' => 'Legal & Accounting Total',
    ];

    /** account_code => new parent account_code */
    private array $reparent = [
        // Insurance items -> Insurance Total
        '6305' => '6900', '6310' => '6900', '6315' => '6900', '6320' => '6900',
        // Online-ordering platforms -> Online Merchant Expenses
        '6460' => '6450', '6461' => '6450',
        // Marketing & Advertising -> Marketing Fees Total
        '6970' => '6200',
        // Promote these to top-level Expense categories
        '6150' => '6000', // Donations
        '6350' => '6000', // Interest Expense
        '6910' => '6000', // Travel and Expense
        '6950' => '6000', // Professional Services
        '6960' => '6000', // Legal & Accounting
        // Sub-accounts under their real category
        '6920' => '6910', // Fuel Expense -> Travel and Expense
        '6715' => '6960', // Legal Services -> Legal & Accounting
        '6705' => '6960', // Accounting Services -> Legal & Accounting
        '6965' => '6400', // Gas -> Utilities Total
        '6905' => '6800', // Auto Services -> Maintenance & Repairs
        '6720' => '6800', // Repairs/Maintenance -> Maintenance & Repairs
        '6725' => '6800', // Pest Control -> Maintenance & Repairs
        '6205' => '6550', // Smallwares -> Equipment Total
        '6250' => '6550', // Uniforms -> Equipment Total
    ];

    /** Codes to remove once empty (deactivated instead if still referenced). */
    private array $delete = ['6300'];

    /** Tables with a restrict FK to chart_of_accounts.id that block a hard delete. */
    private array $refTables = [
        'expense_transactions', 'transaction_mapping_rules',
        'bank_transactions', 'owner_cc_statement_lines', 'owner_cc_description_mappings',
    ];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        // 1. Create missing category accounts first so re-parents can target them.
        $this->info('New categories:');
        foreach ($this->create as $code => $name) {
            $exists = ChartOfAccount::withoutGlobalScopes()->where('account_code', $code)->exists();
            if ($exists) {
                continue;
            }
            $this->line("  + {$code} \"{$name}\" (Expense, top-level)");
            if ($apply) {
                $rootId = ChartOfAccount::withoutGlobalScopes()->where('account_code', '6000')->value('id');
                ChartOfAccount::create([
                    'account_code' => $code,
                    'account_name' => $name,
                    'account_type' => 'Expense',
                    'parent_account_id' => $rootId,
                    'is_active' => true,
                    'is_system_account' => false,
                ]);
            }
        }

        $byCode = ChartOfAccount::withoutGlobalScopes()->get()->keyBy('account_code');

        // 2. Renames.
        $renamed = 0;
        $this->newLine();
        $this->info('Renames (category parents → "… Total"):');
        foreach ($this->renames as $code => $newName) {
            $acct = $byCode->get($code);
            if (! $acct) {
                $this->warn("  skip {$code} — not found");
                continue;
            }
            if ($acct->account_name === $newName) {
                continue;
            }
            $this->line("  {$code}: \"{$acct->account_name}\" → \"{$newName}\"");
            if ($apply) {
                ChartOfAccount::withoutGlobalScopes()->whereKey($acct->id)->update(['account_name' => $newName]);
                $renamed++;
            }
        }

        // 3. Re-parenting.
        $moved = 0;
        $this->newLine();
        $this->info('Re-categorisation (fix wrong parents):');
        foreach ($this->reparent as $code => $parentCode) {
            $acct = $byCode->get($code);
            $parent = $byCode->get($parentCode);
            if (! $acct || ! $parent) {
                $this->warn("  skip {$code} — account or target parent not found");
                continue;
            }
            if ((int) $acct->parent_account_id === (int) $parent->id) {
                continue;
            }
            $this->line("  {$code} \"{$acct->account_name}\" → under {$parentCode} \"{$parent->account_name}\"");
            if ($apply) {
                ChartOfAccount::withoutGlobalScopes()->whereKey($acct->id)->update(['parent_account_id' => $parent->id]);
                $moved++;
            }
        }

        // 4. Remove now-empty accounts (deactivate if still referenced by transactions).
        $removed = 0;
        $this->newLine();
        $this->info('Removals:');
        foreach ($this->delete as $code) {
            $acct = $byCode->get($code);
            if (! $acct) {
                continue;
            }
            // Re-count children live (they may have just moved out under --apply).
            $childCount = ChartOfAccount::withoutGlobalScopes()->where('parent_account_id', $acct->id)->count();
            if ($childCount > 0) {
                $this->warn("  skip {$code} \"{$acct->account_name}\" — still has {$childCount} sub-account(s)");
                continue;
            }
            $refs = $this->referenceCount($acct->id);
            if ($refs > 0) {
                $this->line("  {$code} \"{$acct->account_name}\" — referenced by {$refs} transaction(s); deactivating instead of deleting");
                if ($apply) {
                    ChartOfAccount::withoutGlobalScopes()->whereKey($acct->id)->update(['is_active' => false]);
                }
            } else {
                $this->line("  {$code} \"{$acct->account_name}\" — deleting (empty, unreferenced)");
                if ($apply) {
                    ChartOfAccount::withoutGlobalScopes()->whereKey($acct->id)->delete();
                    $removed++;
                }
            }
        }

        $this->newLine();
        $this->info($apply
            ? "Applied — renamed {$renamed}, re-filed {$moved}, removed {$removed}."
            : 'Dry run only. Re-run with --apply to write.');

        return self::SUCCESS;
    }

    private function referenceCount(int $accountId): int
    {
        $total = 0;
        foreach ($this->refTables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'coa_id')) {
                $total += DB::table($table)->where('coa_id', $accountId)->count();
            }
        }

        return $total;
    }
}
