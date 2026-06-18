<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\DailyReport;
use App\Models\ExpenseTransaction;
use App\Models\RevenueIncomeType;
use App\Models\Store;
use App\Models\User;
use App\Services\DashboardMetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Phase 4 — Dashboard circular metrics.
 */
class DashboardMetricsTest extends TestCase
{
    use RefreshDatabase;

    private function coa(string $code, string $type): ChartOfAccount
    {
        return ChartOfAccount::create([
            'account_code' => $code,
            'account_name' => "Acct {$code}",
            'account_type' => $type,
            'is_active' => true,
        ]);
    }

    /**
     * Create a report whose net sales come from a real revenue line item
     * (net_sales is computed from revenues, not the cached column).
     */
    private function reportWithSales(int $storeId, $date, float $netSales, float $projected): void
    {
        $type = RevenueIncomeType::firstOrCreate(['name' => 'Test Revenue'], ['is_active' => true]);

        DailyReport::factory()->create([
            'store_id' => $storeId,
            'report_date' => $date,
            'projected_sales' => $projected,
            'coupons_received' => 0,
            'adjustments_overrings' => 0,
        ])->revenues()->create(['revenue_income_type_id' => $type->id, 'amount' => $netSales]);
    }

    private function metrics(User $user): array
    {
        $this->actingAs($user);

        return app(DashboardMetricsService::class)->forUser(
            Carbon::now()->startOfMonth(),
            Carbon::now()->endOfMonth()
        );
    }

    /** @test */
    public function it_computes_sales_and_cost_metrics_with_variance(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $store = Store::factory()->create(['created_by' => $owner->id]);
        $when = Carbon::now()->startOfMonth()->addDay();

        // Net sales 10,000 vs projected 8,000 → $2,000 ahead.
        $this->reportWithSales($store->id, $when, 6000, 5000);
        $this->reportWithSales($store->id, $when->copy()->addDay(), 4000, 3000);

        $food = $this->coa('5100', 'COGS');
        $payroll = $this->coa('6600', 'Expense');
        $rent = $this->coa('6500', 'Expense');

        ExpenseTransaction::factory()->create(['store_id' => $store->id, 'coa_id' => $food->id, 'amount' => 3000, 'transaction_date' => $when]);   // 30%
        ExpenseTransaction::factory()->create(['store_id' => $store->id, 'coa_id' => $payroll->id, 'amount' => 3500, 'transaction_date' => $when]); // 35%
        ExpenseTransaction::factory()->create(['store_id' => $store->id, 'coa_id' => $rent->id, 'amount' => 800, 'transaction_date' => $when]);     // 8%

        $m = $this->metrics($owner);

        // Sales
        $this->assertTrue($m['sales']['has_data']);
        $this->assertSame(10000.0, $m['sales']['actual']);
        $this->assertSame(2000.0, $m['sales']['variance']);
        $this->assertTrue($m['sales']['ahead']);

        // Food: 30% vs 30% target → on target, "ahead" (<= target)
        $this->assertSame(30.0, $m['food']['percent']);
        $this->assertTrue($m['food']['ahead']);

        // Payroll: 35% vs 30% target → over → behind
        $this->assertSame(35.0, $m['payroll']['percent']);
        $this->assertFalse($m['payroll']['ahead']);
        $this->assertSame(5.0, $m['payroll']['variance']);

        // Rent: 8% vs 10% target → under → ahead
        $this->assertSame(8.0, $m['rent']['percent']);
        $this->assertTrue($m['rent']['ahead']);
    }

    /** @test */
    public function metrics_are_greyed_out_when_no_data_exists(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        Store::factory()->create(['created_by' => $owner->id]);

        $m = $this->metrics($owner);

        foreach (['sales', 'food', 'payroll', 'rent'] as $k) {
            $this->assertFalse($m[$k]['has_data'], "{$k} should be greyed out with no data.");
            $this->assertNull($m[$k]['variance_label']);
        }
        $this->assertSame('—', $m['food']['display']);
    }

    /** @test */
    public function metrics_are_tenant_scoped_to_the_owner(): void
    {
        $ownerA = User::factory()->create(['role' => 'owner']);
        $ownerB = User::factory()->create(['role' => 'owner']);
        $storeA = Store::factory()->create(['created_by' => $ownerA->id]);
        $storeB = Store::factory()->create(['created_by' => $ownerB->id]);
        $when = Carbon::now()->startOfMonth()->addDay();

        $this->reportWithSales($storeA->id, $when, 5000, 4000);
        $this->reportWithSales($storeB->id, $when, 9999, 9999);

        // Owner A only sees store A's $5,000 — not B's data.
        $this->assertSame(5000.0, $this->metrics($ownerA)['sales']['actual']);
    }
}
