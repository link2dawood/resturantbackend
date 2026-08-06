<?php

namespace Tests\Feature;

use App\Models\DailyReport;
use App\Models\RevenueIncomeType;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Coverage for the daily-report financial formulas that had no test:
 * the 8.25% sales-tax split (÷1.0825), cash short/over reconciliation, the
 * non-negative cash clamp, online/check/crypto category revenue, and the
 * decomposition invariants that must always hold.
 */
class DailyReportFinancialCalculationsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Build a report with full control over the money inputs and a set of
     * revenue lines described as [['amount' => x, 'category' => 'cash'], ...].
     */
    private function makeReport(array $overrides, array $revenueLines): DailyReport
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $store = Store::factory()->create(['created_by' => $owner->id]);
        $this->actingAs($owner);

        $report = DailyReport::factory()->create(array_merge([
            'store_id' => $store->id,
            'report_date' => now(),
            'created_by' => $owner->id,
            'coupons_received' => 0,
            'adjustments_overrings' => 0,
            'adjustments_cash' => 0,
            'adjustments_credit_card' => 0,
            'credit_cards' => 0,
            'credit_card_tips' => 0,
            'tips' => 0,
            'actual_deposit' => 0,
            'total_customers' => 0,
        ], $overrides));

        foreach ($revenueLines as $line) {
            $category = $line['category'] ?? 'cash';
            $type = RevenueIncomeType::firstOrCreate(
                ['name' => ucfirst($category).' Revenue'],
                ['is_active' => true, 'category' => $category]
            );
            $report->revenues()->create([
                'revenue_income_type_id' => $type->id,
                'amount' => $line['amount'],
            ]);
        }

        return $report->refresh();
    }

    /** @test */
    public function tax_is_the_8_25_percent_split_off_net_sales(): void
    {
        // Net Sales 1082.50 → pre-tax 1000.00, tax 82.50 (÷ 1.0825).
        $r = $this->makeReport([], [['amount' => 1082.50, 'category' => 'cash']]);

        $this->assertEqualsWithDelta(1082.50, $r->net_sales, 0.01);
        $this->assertEqualsWithDelta(1000.00, $r->sales_pre_tax, 0.01);
        $this->assertEqualsWithDelta(82.50, $r->tax, 0.01);
    }

    /** @test */
    public function net_sales_always_decomposes_into_pre_tax_plus_tax(): void
    {
        // Invariant: net_sales == sales_pre_tax + tax, for arbitrary inputs.
        $r = $this->makeReport(
            ['coupons_received' => 15, 'adjustments_overrings' => 7.5, 'credit_card_tips' => 20],
            [['amount' => 873.19, 'category' => 'cash']]
        );

        $this->assertEqualsWithDelta($r->net_sales, $r->sales_pre_tax + $r->tax, 0.001);
    }

    /** @test */
    public function credit_card_total_equals_sales_plus_tips(): void
    {
        $r = $this->makeReport(
            ['credit_cards' => 300, 'credit_card_tips' => 45],
            [['amount' => 500, 'category' => 'cash']]
        );

        $this->assertEqualsWithDelta(300.0, $r->credit_card_sales, 0.01);
        $this->assertEqualsWithDelta(345.0, $r->credit_card_total, 0.01);
        // Invariant.
        $this->assertEqualsWithDelta($r->credit_card_total, $r->credit_card_sales + $r->credit_card_tips, 0.001);
        // Tips ride on the card, so they reduce net sales: 500 − 45 = 455.
        $this->assertEqualsWithDelta(455.0, $r->net_sales, 0.01);
    }

    /** @test */
    public function short_is_reported_when_the_deposit_is_under_cash_owed(): void
    {
        // 1000 cash revenue, nothing else → cash to account for = 1000.
        $r = $this->makeReport(
            ['actual_deposit' => 950],
            [['amount' => 1000, 'category' => 'cash']]
        );

        $this->assertEqualsWithDelta(1000.0, $r->cash_to_account_for, 0.01);
        $this->assertEqualsWithDelta(-50.0, $r->short, 0.01);
        $this->assertEqualsWithDelta(0.0, $r->over, 0.01);
    }

    /** @test */
    public function over_is_reported_when_the_deposit_exceeds_cash_owed(): void
    {
        $r = $this->makeReport(
            ['actual_deposit' => 1075],
            [['amount' => 1000, 'category' => 'cash']]
        );

        $this->assertEqualsWithDelta(1000.0, $r->cash_to_account_for, 0.01);
        $this->assertEqualsWithDelta(75.0, $r->over, 0.01);
        $this->assertEqualsWithDelta(0.0, $r->short, 0.01);
    }

    /** @test */
    public function online_check_and_crypto_revenue_are_bucketed_and_removed_from_cash_owed(): void
    {
        // 400 cash + 300 online + 200 check + 100 crypto = 1000 net sales.
        $r = $this->makeReport([], [
            ['amount' => 400, 'category' => 'cash'],
            ['amount' => 300, 'category' => 'online'],
            ['amount' => 200, 'category' => 'check'],
            ['amount' => 100, 'category' => 'crypto'],
        ]);

        $this->assertEqualsWithDelta(1000.0, $r->net_sales, 0.01);
        $this->assertEqualsWithDelta(300.0, $r->online_platform_revenue, 0.01);
        $this->assertEqualsWithDelta(200.0, $r->checks_revenue, 0.01);
        $this->assertEqualsWithDelta(100.0, $r->crypto_revenue, 0.01);
        // Only the cash portion is owed to the drawer: 1000 − 300 − 200 − 100 = 400.
        $this->assertEqualsWithDelta(400.0, $r->cash_to_account_for, 0.01);
    }

    /** @test */
    public function cash_to_account_for_never_goes_negative(): void
    {
        // Paid out more cash (transaction expense 300) than taken in (100) →
        // raw result -200, clamped to 0.
        $r = $this->makeReport([], [['amount' => 100, 'category' => 'cash']]);
        $r->transactions()->create(['amount' => 300]);
        $r->refresh();

        $this->assertEqualsWithDelta(300.0, $r->total_transaction_expenses, 0.01);
        $this->assertEqualsWithDelta(0.0, $r->cash_to_account_for, 0.01);
    }
}
