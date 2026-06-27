<?php

namespace Tests\Feature;

use App\Models\DailyReport;
use App\Models\RevenueIncomeType;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Daily report — net sales / cash reconciliation after splitting Credit Card
 * into sales + tips and adding the Cash / Credit-card / Tips adjustments.
 */
class DailyReportReconciliationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function net_sales_and_cash_to_account_reconcile_with_adjustments_and_tips(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $store = Store::factory()->create(['created_by' => $owner->id]);
        $this->actingAs($owner);

        $type = RevenueIncomeType::firstOrCreate(['name' => 'Test Revenue'], ['is_active' => true, 'category' => 'cash']);

        $report = DailyReport::factory()->create([
            'store_id' => $store->id,
            'report_date' => now(),
            'coupons_received' => 10,
            'adjustments_overrings' => 5,
            'adjustments_cash' => 20,
            'adjustments_credit_card' => 15,
            'tips' => 50,
            'credit_cards' => 400,      // card SALES only
            'credit_card_tips' => 30,   // paid out in cash
        ]);
        $report->revenues()->create(['revenue_income_type_id' => $type->id, 'amount' => 1000]);
        $report->refresh();

        // Net Sales = 1000 − 10 − 5 − 20 − 15 − 50 = 900
        $this->assertEqualsWithDelta(900.0, $report->net_sales, 0.01);

        // Cash To Account For = 900 − expenses(0) − online(0) − CC sales(400)
        //   − checks(0) − crypto(0) − CC tips(30) = 470
        $this->assertEqualsWithDelta(470.0, $report->cash_to_account_for, 0.01);
    }

    /** @test */
    public function each_adjustment_and_tips_reduce_net_sales_by_its_amount(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $store = Store::factory()->create(['created_by' => $owner->id]);
        $this->actingAs($owner);
        $type = RevenueIncomeType::firstOrCreate(['name' => 'Test Revenue'], ['is_active' => true, 'category' => 'cash']);

        $base = DailyReport::factory()->create([
            'store_id' => $store->id, 'report_date' => now(),
            'coupons_received' => 0, 'adjustments_overrings' => 0,
            'adjustments_cash' => 0, 'adjustments_credit_card' => 0, 'tips' => 0,
        ]);
        $base->revenues()->create(['revenue_income_type_id' => $type->id, 'amount' => 1000]);
        $this->assertEqualsWithDelta(1000.0, $base->refresh()->net_sales, 0.01);

        // +25 in each of the three new buckets → net drops by 75.
        $base->update(['adjustments_cash' => 25, 'adjustments_credit_card' => 25, 'tips' => 25]);
        $this->assertEqualsWithDelta(925.0, $base->refresh()->net_sales, 0.01);
    }
}
