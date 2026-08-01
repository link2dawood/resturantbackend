<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\DailyReport;
use App\Models\RevenueIncomeType;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * transactions:clean must zero the financial numbers while leaving the Chart of
 * Accounts, stores, users, and vendors untouched.
 */
class CleanTransactionsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_clears_transaction_rows_but_preserves_structure(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $store = Store::factory()->create(['created_by' => $owner->id]);
        $type = RevenueIncomeType::firstOrCreate(['name' => 'Cash'], ['is_active' => true, 'category' => 'cash']);

        // A daily report with a revenue line + an expense_transaction (the numbers).
        $report = DailyReport::factory()->create([
            'store_id' => $store->id, 'report_date' => now(), 'created_by' => $owner->id,
        ]);
        $report->revenues()->create(['revenue_income_type_id' => $type->id, 'amount' => 1000]);

        $coa = ChartOfAccount::withoutGlobalScopes()->first()
            ?? ChartOfAccount::create(['account_code' => '9999', 'account_name' => 'T', 'account_type' => 'expense']);

        DB::table('expense_transactions')->insert([
            'daily_report_id' => $report->id, 'coa_id' => $coa->id, 'store_id' => $store->id,
            'transaction_type' => 'cash', 'transaction_date' => now()->format('Y-m-d'),
            'amount' => 20, 'duplicate_check_hash' => 'test-'.uniqid(),
            'created_by' => $owner->id, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $coaCountBefore = ChartOfAccount::withoutGlobalScopes()->count();

        // Sanity: the numbers exist before cleaning.
        $this->assertSame(1, DB::table('daily_reports')->count());
        $this->assertSame(1, DB::table('expense_transactions')->count());
        $this->assertSame(1, DB::table('daily_report_revenues')->count());

        $this->artisan('transactions:clean', ['--force' => true])
            ->expectsConfirmation('Proceed? Chart of Accounts and other data stay intact.', 'yes')
            ->assertExitCode(0);

        // Numbers are gone…
        $this->assertSame(0, DB::table('daily_reports')->count());
        $this->assertSame(0, DB::table('expense_transactions')->count());
        $this->assertSame(0, DB::table('daily_report_revenues')->count());

        // …but structure & config stay exactly as they were.
        $this->assertSame($coaCountBefore, ChartOfAccount::withoutGlobalScopes()->count());
        $this->assertDatabaseHas('stores', ['id' => $store->id]);
        $this->assertDatabaseHas('users', ['id' => $owner->id]);
        $this->assertDatabaseHas('revenue_income_types', ['id' => $type->id]);
    }

    public function test_dry_run_deletes_nothing(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $store = Store::factory()->create(['created_by' => $owner->id]);
        DailyReport::factory()->create([
            'store_id' => $store->id, 'report_date' => now(), 'created_by' => $owner->id,
        ]);

        $this->artisan('transactions:clean')->assertExitCode(0);

        $this->assertSame(1, DB::table('daily_reports')->count());
    }
}
