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
 * Credit Card field is the TOTAL (sales + tips); CC sales = total − tips. Tips
 * come out of the card total (not cash), reduce net sales, and the Square fee is
 * 2.45% of the total. Average ticket is based on Sales (Pre-Tax).
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
            'adjustments_cash' => 0, 'adjustments_credit_card' => 0,
            'credit_cards' => 0, 'credit_card_tips' => 0,
        ], $overrides));
        $report->revenues()->create(['revenue_income_type_id' => $type->id, 'amount' => 1000]);

        return $report->refresh();
    }

    /** @test */
    public function net_sales_subtracts_cc_tips_and_cash_uses_cc_sales(): void
    {
        $r = $this->report([
            'coupons_received' => 10,
            'adjustments_overrings' => 5,
            'adjustments_cash' => 20,
            'adjustments_credit_card' => 15,
            'credit_cards' => 400,      // card TOTAL (sales + tips)
            'credit_card_tips' => 30,
        ]);

        // Net Sales = 1000 − 10 − 5 − 20 − 15 − 30 (CC tips) = 920
        $this->assertEqualsWithDelta(920.0, $r->net_sales, 0.01);
        // CC sales = 400 − 30 = 370
        $this->assertEqualsWithDelta(370.0, $r->credit_card_sales, 0.01);
        // Cash = 920 − CC sales(370) = 550 (tips do NOT reduce cash)
        $this->assertEqualsWithDelta(550.0, $r->cash_to_account_for, 0.01);
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
