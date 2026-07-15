<?php

namespace Tests\Feature;

use App\Models\DailyReport;
use App\Models\RevenueIncomeType;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Daily report — net sales / cash reconciliation.
 *
 * Credit Card SALES is entered; the card TOTAL = sales + tips (calculated). Tips
 * ride on the card (not cash), reduce net sales, and the Square fee is 2.45% of
 * the total. Average ticket is based on Sales (Pre-Tax). There is no separate
 * "Adjustment for Credit Card" — tips cover it.
 */
class DailyReportReconciliationTest extends TestCase
{
    use RefreshDatabase;

    private function report(array $overrides = []): DailyReport
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $store = Store::factory()->create(['created_by' => $owner->id]);
        $this->actingAs($owner);
        $type = RevenueIncomeType::firstOrCreate(['name' => 'Test Revenue'], ['is_active' => true, 'category' => 'cash']);

        $report = DailyReport::factory()->create(array_merge([
            'store_id' => $store->id, 'report_date' => now(),
            'coupons_received' => 0, 'adjustments_overrings' => 0,
            'adjustments_cash' => 0,
            'credit_cards' => 0, 'credit_card_tips' => 0,
        ], $overrides));
        $report->revenues()->create(['revenue_income_type_id' => $type->id, 'amount' => 1000]);

        return $report->refresh();
    }

    /** @test */
    public function cc_total_is_sales_plus_tips_and_cash_uses_cc_sales(): void
    {
        $r = $this->report([
            'coupons_received' => 10,
            'adjustments_overrings' => 5,
            'adjustments_cash' => 20,
            'credit_cards' => 370,      // card SALES (entered)
            'credit_card_tips' => 30,
        ]);

        // Net Sales = 1000 − 10 − 5 − 20 − 30 (CC tips) = 935
        $this->assertEqualsWithDelta(935.0, $r->net_sales, 0.01);
        // CC sales = what was entered; total = sales + tips = 400
        $this->assertEqualsWithDelta(370.0, $r->credit_card_sales, 0.01);
        $this->assertEqualsWithDelta(400.0, $r->credit_card_total, 0.01);
        // Cash = 935 − CC sales(370) = 565 (tips ride on the card, not cash)
        $this->assertEqualsWithDelta(565.0, $r->cash_to_account_for, 0.01);
    }

    /** @test */
    public function average_ticket_is_based_on_sales_pre_tax(): void
    {
        $r = $this->report(['total_customers' => 10]);

        $expected = $r->sales_pre_tax / 10;
        $this->assertEqualsWithDelta($expected, $r->average_ticket, 0.01);
        // …and that's the pre-tax figure, not net sales / customers.
        $this->assertNotEqualsWithDelta($r->net_sales / 10, $r->average_ticket, 0.01);
    }
}
