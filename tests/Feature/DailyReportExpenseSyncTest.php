<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\DailyReport;
use App\Models\ExpenseTransaction;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Expenses typed into the daily report must land in expense_transactions (the
 * table the P&L reads) — otherwise they never appear on the P&L (Bugs 2 & 3).
 */
class DailyReportExpenseSyncTest extends TestCase
{
    use RefreshDatabase;

    private function manager(Store $store): User
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $store->assignedManagers()->attach($manager->id);

        return $manager;
    }

    private function payload(Store $store, ChartOfAccount $coa, float $amount): array
    {
        return [
            'store_id' => $store->id,
            'report_date' => now()->format('Y-m-d'),
            'projected_sales' => 1000.00,
            'gross_sales' => 1200.00,
            'total_paid_outs' => $amount,
            'total_customers' => 50,
            'credit_cards' => 800.00,
            'actual_deposit' => 1200.00,
            'transactions' => [
                ['company' => "Sam's Club", 'amount' => $amount, 'transaction_type' => $coa->id],
            ],
        ];
    }

    /** @test */
    public function a_daily_report_expense_creates_an_expense_transaction_for_the_pl(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $store = Store::factory()->create(['created_by' => $owner->id]);
        $manager = $this->manager($store);
        $cogs = ChartOfAccount::create(['account_code' => '5100', 'account_name' => 'COGS - Food Purchases', 'account_type' => 'COGS', 'is_active' => true]);

        $this->actingAs($manager)->post('/daily-reports', $this->payload($store, $cogs, 20.00))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('expense_transactions', [
            'store_id' => $store->id,
            'coa_id' => $cogs->id,
            'amount' => 20.00,
        ]);
    }

    /** @test */
    public function re_saving_the_report_does_not_duplicate_the_expense(): void
    {
        // Admin bypasses the store-access/trial gates so we isolate the sync logic.
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'owner']);
        $store = Store::factory()->create(['created_by' => $owner->id]);
        $cogs = ChartOfAccount::create(['account_code' => '5100', 'account_name' => 'COGS - Food Purchases', 'account_type' => 'COGS', 'is_active' => true]);

        $this->actingAs($admin)->post('/daily-reports', $this->payload($store, $cogs, 20.00))->assertSessionDoesntHaveErrors();
        $report = DailyReport::where('store_id', $store->id)->latest('id')->first();

        // Edit the report (amount changed) — should replace, not add a second row.
        $this->actingAs($admin)->put("/daily-reports/{$report->id}", array_merge(
            $this->payload($store, $cogs, 35.00),
            ['is_active' => 1]
        ))->assertSessionDoesntHaveErrors();

        $synced = ExpenseTransaction::where('daily_report_id', $report->id)
            ->where('coa_id', $cogs->id)->get();
        $this->assertCount(1, $synced, 'Re-saving must not duplicate the expense');
        $this->assertEqualsWithDelta(35.00, (float) $synced->first()->amount, 0.01);
    }
}
