<?php

namespace App\Console\Commands;

use App\Models\BankAccount;
use App\Models\DailyReport;
use App\Models\ExpenseTransaction;
use App\Models\ImportBatch;
use App\Models\OwnerCcStatementImport;
use App\Models\Store;
use App\Models\ThirdPartyStatement;
use App\Models\User;
use App\Services\CoaTemplateService;
use Illuminate\Console\Command;

/**
 * Phase 4 — Tenant migration utility.
 *
 * Brings existing (pre-SaaS) Fann's Philly data into the multi-tenant model.
 * Tenancy is derived from store ownership, so "migrating" means making sure
 * every store has an owner (the tenant) — orphan stores are assigned to the
 * Franchisor so they remain visible and correctly scoped. Also ensures the
 * standard chart of accounts exists and reports any untenanted records.
 *
 * Idempotent and read-mostly: pass --dry-run to report without changing data.
 */
class MigrateExistingTenants extends Command
{
    protected $signature = 'tenant:migrate-existing {--dry-run : Report only, make no changes}';

    protected $description = 'Migrate existing data into the multi-tenant structure (assign orphan stores, seed CoA, audit tenanting)';

    public function handle(CoaTemplateService $coa): int
    {
        $dry = (bool) $this->option('dry-run');
        $this->info($dry ? 'DRY RUN — no changes will be made.' : 'Migrating existing data into the multi-tenant structure...');

        // 1. Ensure the Franchisor (brand owner) exists.
        $franchisor = $dry ? User::where('email', 'franchisor@system.local')->first() : User::getOrCreateFranchisor();
        $this->line('Franchisor: '.($franchisor?->email ?? '(would be created)'));

        // 2. Assign stores with no explicit owner (no owner_store pivot row) to the
        //    Franchisor so every store is tenanted.
        $orphans = Store::doesntHave('owners')->get();

        foreach ($orphans as $store) {
            if (! $dry && $franchisor) {
                $store->owners()->syncWithoutDetaching([$franchisor->id]);
            }
        }
        $this->line('Stores without an explicit owner assigned to Franchisor: '.$orphans->count());

        // 3. Ensure the standard chart of accounts exists (new-signup template).
        if (! $dry) {
            $result = $coa->apply();
            $this->line("Chart of accounts: {$result['created']} created, {$result['existing']} already present.");
        }

        // 4. Audit: report records not tied to any store (NULL = corporate/shared).
        $this->newLine();
        $this->info('Untenanted records (NULL store_id — corporate/shared, review if unexpected):');
        foreach ([
            'daily_reports' => DailyReport::class,
            'expense_transactions' => ExpenseTransaction::class,
            'third_party_statements' => ThirdPartyStatement::class,
            'bank_accounts' => BankAccount::class,
            'import_batches' => ImportBatch::class,
            'owner_cc_statement_imports' => OwnerCcStatementImport::class,
        ] as $label => $model) {
            $count = $model::withoutTenantScope()->whereNull('store_id')->count();
            $this->line(sprintf('  %-28s %d', $label, $count));
        }

        $this->newLine();
        $this->info('Done.');

        return self::SUCCESS;
    }
}
