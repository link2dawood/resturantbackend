<?php

namespace App\Console\Commands;

use App\Models\ChartOfAccount;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-off cleanup for the Expense chart: rename the category parents to
 * "… Total" and re-file the accounts the number-based backfill miscategorised
 * (e.g. the insurance accounts that landed under Delivery Service Fees).
 *
 * Only the codes listed below are touched; anything missing is skipped with a
 * note. Dry run by default; pass --apply to write. Idempotent — re-running after
 * an apply is a no-op.
 */
class RecategorizeChartOfAccounts extends Command
{
    protected $signature = 'coa:recategorize {--apply : Actually write the changes (default is a dry run)}';

    protected $description = 'Rename Expense parents to "… Total" and re-file miscategorised accounts';

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
    ];

    /** account_code => new parent account_code (fix miscategorised items) */
    private array $reparent = [
        '6305' => '6900', // Life Insurance      -> Insurance Total
        '6310' => '6900', // Car Insurance       -> Insurance Total
        '6315' => '6900', // Store Insurance     -> Insurance Total (was under Car Insurance)
        '6320' => '6900', // Equipment Insurance -> Insurance Total
        '6460' => '6450', // Relish Expenses     -> Online Merchant Expenses
        '6461' => '6450', // Square Online Order. -> Online Merchant Expenses
        '6970' => '6200', // Marketing & Advertising -> Marketing Fees Total
        '6150' => '6000', // Donations           -> top-level (was under Merchant Processing Fees)
        '6350' => '6000', // Interest Expense    -> top-level (was under Delivery Service Fees)
    ];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $byCode = ChartOfAccount::withoutGlobalScopes()->get()->keyBy('account_code');

        $renamed = 0;
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

        $this->newLine();
        $this->info($apply
            ? "Applied — renamed {$renamed}, re-filed {$moved}."
            : 'Dry run only. Re-run with --apply to write.');

        return self::SUCCESS;
    }
}
