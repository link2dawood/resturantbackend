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
    public function the_create_form_shows_a_dedicated_tips_block_feeding_a_read_only_credit_card_tips(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $store = Store::factory()->create(['created_by' => $owner->id]);

        $response = $this->actingAs($owner)->get(route('daily-reports.create-form', [
            'store_id' => $store->id,
            'report_date' => now()->format('Y-m-d'),
        ]));

        $response->assertStatus(200)
            // The dedicated Tips block is the single entry point for tips.
            ->assertSee('id="tipsBlockInput"', false)
            ->assertSee('Credit Card Tips:')
            // Credit Card (tips) in the box is now a read-only mirror, not an input.
            ->assertSee('id="creditCardTipsCalc"', false)
            ->assertSee('Auto-filled from the Tips block')
            // Credit Card Total is entered; Business Credit Card Sales = Total − tips.
            ->assertSee('Credit Card Total:')
            ->assertSee('id="creditCardTotalInput"', false)
            ->assertSee('Business Credit Card Sales:')
            ->assertSee('id="creditCardsHidden"', false);
    }

    /** @test */
    public function saving_a_new_report_lands_on_its_view_page(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $store = Store::factory()->create(['created_by' => $owner->id]);
        $manager = User::factory()->create(['role' => 'manager']);
        $store->assignedManagers()->attach($manager->id);

        $response = $this->actingAs($manager)->post('/daily-reports', [
            'store_id' => $store->id,
            'report_date' => now()->format('Y-m-d'),
            'projected_sales' => 1000.00,
            'gross_sales' => 1200.00,
            'total_paid_outs' => 100.00,
            'total_customers' => 50,
            'credit_cards' => 800.00,
            'actual_deposit' => 1200.00,
        ]);

        $response->assertSessionDoesntHaveErrors();
        $report = \App\Models\DailyReport::where('store_id', $store->id)->latest('id')->first();
        $this->assertNotNull($report);
        // Lands on the report's own view page, not a list or blank form.
        $response->assertRedirect(route('daily-reports.show', $report));
    }

    /** @test */
    public function the_create_form_offers_the_holiday_picker(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $store = Store::factory()->create(['created_by' => $owner->id]);

        $response = $this->actingAs($owner)->get(route('daily-reports.create-form', [
            'store_id' => $store->id,
            'report_date' => now()->format('Y-m-d'),
        ]));

        $response->assertStatus(200)
            ->assertSee('id="holidayOptions"', false)
            ->assertSee('Labor Day')
            ->assertSee('Thanksgiving Day');
    }

    /** @test */
    public function the_edit_form_prefills_the_tips_block_from_the_saved_value(): void
    {
        $r = $this->report(['credit_card_tips' => 42]);

        $response = $this->actingAs(User::where('role', 'owner')->first())
            ->get("/daily-reports/{$r->id}/edit");

        $response->assertStatus(200)
            ->assertSee('id="tipsBlockInput"', false)
            // The saved tip value is prefilled into the block's input (decimal:2 cast).
            ->assertSee('value="42.00"', false)
            ->assertSee('id="creditCardTipsCalc"', false);
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
