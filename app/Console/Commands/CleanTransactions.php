<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Zero out all transactional / financial records so every report and dashboard
 * reads $0 — WITHOUT touching the Chart of Accounts, stores, users, vendors, or
 * any configuration. Use this to wipe test data before going live.
 *
 * Cleared (the actual numbers): daily reports + their revenue/expense rows,
 * expense_transactions, bank imports, owner-CC imports, third-party statements,
 * P&L snapshots, and audit logs.
 *
 * Preserved: chart_of_accounts, stores, users, vendors, holidays, revenue
 * income types, transaction types, mapping rules, bank accounts, permissions,
 * sales projections, and KPI targets.
 *
 * Dry run by default; pass --force to actually delete. Optional flags let you
 * also clear planning numbers and learned rules if you want a truly blank slate.
 */
class CleanTransactions extends Command
{
    protected $signature = 'transactions:clean
        {--force : Actually delete the rows (default is a dry run)}
        {--with-projections : Also clear sales_projections and kpi_targets}
        {--with-mapping-rules : Also clear learned auto-categorization rules}';

    protected $description = 'Zero all transaction numbers (dry run by default) while preserving Chart of Accounts and configuration';

    /**
     * Core transaction tables in child -> parent delete order. FK checks are
     * disabled during the run, so order only affects the report, not safety.
     */
    private array $core = [
        'daily_report_revenues',
        'daily_report_transactions',
        'bank_transactions',
        'expense_transactions',
        'owner_cc_statement_lines',
        'owner_cc_statement_imports',
        'import_batches',
        'third_party_statements',
        'pl_snapshots',
        'daily_reports',
        'audit_logs',
    ];

    /** Tables that must NEVER be cleared by this command, shown for reassurance. */
    private array $preserved = [
        'chart_of_accounts', 'coa_store_assignments', 'stores', 'users',
        'vendors', 'vendor_aliases', 'vendor_store_assignments', 'holidays',
        'revenue_income_types', 'transaction_types', 'bank_accounts',
        'permissions', 'role_permissions', 'states', 'subscriptions',
    ];

    public function handle(): int
    {
        $tables = $this->core;

        if ($this->option('with-projections')) {
            $tables[] = 'sales_projections';
            $tables[] = 'kpi_targets';
        }
        if ($this->option('with-mapping-rules')) {
            $tables[] = 'transaction_mapping_rules';
            $tables[] = 'owner_cc_description_mappings';
        }

        // Only operate on tables that actually exist in this database.
        $tables = array_values(array_filter($tables, fn ($t) => Schema::hasTable($t)));

        $force = (bool) $this->option('force');

        $this->newLine();
        $this->info($force
            ? 'CLEANING transaction data…'
            : 'DRY RUN — nothing will be deleted. Rows that WOULD be cleared:');
        $this->newLine();

        $counts = [];
        foreach ($tables as $t) {
            $counts[$t] = DB::table($t)->count();
        }
        $total = array_sum($counts);

        foreach ($counts as $t => $c) {
            $this->line(sprintf('  %-30s %10s', $t, number_format($c)));
        }
        $this->line(str_repeat(' ', 2).str_repeat('─', 41));
        $this->line(sprintf('  %-30s %10s', 'TOTAL rows', number_format($total)));
        $this->newLine();
        $this->comment('Preserved (never touched): '.implode(', ', $this->preserved).'.');
        $this->newLine();

        if (! $force) {
            $this->warn('This was a DRY RUN. Re-run with --force to delete the rows above.');

            return self::SUCCESS;
        }

        if ($total === 0) {
            $this->info('Nothing to clean — all transaction tables are already empty.');

            return self::SUCCESS;
        }

        $this->warn("About to permanently delete {$total} rows across ".count($tables).' tables.');
        if (! $this->confirm('Proceed? Chart of Accounts and other data stay intact.', false)) {
            $this->info('Aborted. Nothing was deleted.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($tables) {
            Schema::disableForeignKeyConstraints();
            foreach ($tables as $t) {
                $deleted = DB::table($t)->delete();
                $this->line(sprintf('  cleared %-30s %10s rows', $t, number_format($deleted)));
            }
            Schema::enableForeignKeyConstraints();
        });

        $this->newLine();
        $this->info('Done. All transaction numbers are now zero. Chart of Accounts and configuration were preserved.');

        return self::SUCCESS;
    }
}
