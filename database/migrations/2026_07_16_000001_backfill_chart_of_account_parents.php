<?php

use App\Models\ChartOfAccount;
use Illuminate\Database\Migrations\Migration;

/**
 * The chart's hierarchy was never stored — `parent_account_id` was NULL on every
 * account and the tree was inferred from the account NUMBER (code ranges). That
 * meant an account's number decided its parent, so auto-coded accounts landed in
 * whatever group their number happened to fall into (e.g. insurance accounts
 * numbered 63xx showing under "Delivery Service Fees" 6300), and an admin had no
 * way to move them.
 *
 * This backfills `parent_account_id` from the existing numbering so the tree
 * becomes EXPLICIT data. From here the stored parent is the source of truth and
 * an admin can re-parent any account from the edit screen.
 *
 * Idempotent: only fills rows where parent_account_id IS NULL.
 */
return new class extends Migration
{
    public function up(): void
    {
        $accounts = ChartOfAccount::withoutGlobalScopes()->get(['id', 'account_code', 'parent_account_id']);
        $idByCode = $accounts->pluck('id', 'account_code');

        foreach ($accounts as $account) {
            if ($account->parent_account_id !== null) {
                continue; // already set — never override an admin's choice
            }

            $parentCode = ChartOfAccount::inferParentCode((string) $account->account_code);
            $parentId = $parentCode ? ($idByCode[$parentCode] ?? null) : null;

            if ($parentId && $parentId !== $account->id) {
                ChartOfAccount::withoutGlobalScopes()
                    ->whereKey($account->id)
                    ->update(['parent_account_id' => $parentId]);
            }
        }
    }

    public function down(): void
    {
        // No-op: we can't tell backfilled parents from ones an admin set by hand,
        // and clearing them would throw away real data.
    }
};
