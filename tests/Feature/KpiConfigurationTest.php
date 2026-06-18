<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\DailyReport;
use App\Models\ExpenseTransaction;
use App\Models\KpiTarget;
use App\Models\Store;
use App\Models\User;
use App\Services\DashboardMetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Phase 4 — KPI configuration (per-tenant targets).
 */
class KpiConfigurationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function owner_can_view_the_kpi_setup_screen(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        Store::factory()->create(['created_by' => $owner->id]);

        $this->actingAs($owner)->get(route('kpi.edit'))->assertOk()->assertSee('KPI Targets');
    }

    /** @test */
    public function owner_can_save_per_store_kpi_targets(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $store = Store::factory()->create(['created_by' => $owner->id]);

        $this->actingAs($owner)->put(route('kpi.update'), [
            'targets' => [
                $store->id => ['food_cost_pct' => 28, 'payroll_pct' => 32, 'rent_pct' => 9],
            ],
        ])->assertRedirect(route('kpi.edit'));

        $this->assertDatabaseHas('kpi_targets', [
            'store_id' => $store->id,
            'food_cost_pct' => 28,
            'payroll_pct' => 32,
            'rent_pct' => 9,
            'updated_by' => $owner->id,
        ]);
    }

    /** @test */
    public function an_owner_cannot_write_targets_for_another_tenants_store(): void
    {
        $ownerA = User::factory()->create(['role' => 'owner']);
        $ownerB = User::factory()->create(['role' => 'owner']);
        $storeB = Store::factory()->create(['created_by' => $ownerB->id]);

        // Owner A tries to set targets for store B → silently ignored.
        $this->actingAs($ownerA)->put(route('kpi.update'), [
            'targets' => [$storeB->id => ['food_cost_pct' => 5]],
        ])->assertRedirect();

        $this->assertDatabaseMissing('kpi_targets', ['store_id' => $storeB->id]);
    }

    /** @test */
    public function kpi_targets_are_tenant_scoped(): void
    {
        $ownerA = User::factory()->create(['role' => 'owner']);
        $ownerB = User::factory()->create(['role' => 'owner']);
        $storeA = Store::factory()->create(['created_by' => $ownerA->id]);
        $storeB = Store::factory()->create(['created_by' => $ownerB->id]);
        KpiTarget::create(['store_id' => $storeA->id, 'food_cost_pct' => 20]);
        KpiTarget::create(['store_id' => $storeB->id, 'food_cost_pct' => 40]);

        $this->actingAs($ownerA);
        $this->assertSame(1, KpiTarget::count());
        $this->assertSame(20.0, (float) KpiTarget::first()->food_cost_pct);
    }

    /** @test */
    public function dashboard_uses_the_user_defined_target_over_the_config_default(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $store = Store::factory()->create(['created_by' => $owner->id]);
        $when = Carbon::now()->startOfMonth()->addDay();

        // net_sales is computed from revenue line items (not the cached column).
        $type = \App\Models\RevenueIncomeType::firstOrCreate(['name' => 'Test Revenue'], ['is_active' => true]);
        DailyReport::factory()->create(['store_id' => $store->id, 'report_date' => $when, 'projected_sales' => 9000, 'coupons_received' => 0, 'adjustments_overrings' => 0])
            ->revenues()->create(['revenue_income_type_id' => $type->id, 'amount' => 10000]);
        $food = ChartOfAccount::create(['account_code' => '5100', 'account_name' => 'Food', 'account_type' => 'COGS', 'is_active' => true]);
        ExpenseTransaction::factory()->create(['store_id' => $store->id, 'coa_id' => $food->id, 'amount' => 3000, 'transaction_date' => $when]); // 30%

        // Custom target 25% (config default is 30%).
        KpiTarget::create(['store_id' => $store->id, 'food_cost_pct' => 25]);

        $this->actingAs($owner);
        $m = app(DashboardMetricsService::class)->forUser(
            Carbon::now()->startOfMonth(),
            Carbon::now()->endOfMonth()
        );

        $this->assertSame(25.0, $m['food']['target']);
        $this->assertSame(5.0, $m['food']['variance']); // 30% actual − 25% target
        $this->assertFalse($m['food']['ahead']);        // over target → behind
    }

    /** @test */
    public function managers_cannot_access_kpi_configuration(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);

        $this->actingAs($manager)->get(route('kpi.edit'))->assertForbidden();
    }
}
