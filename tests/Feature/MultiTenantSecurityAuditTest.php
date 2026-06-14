<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\DailyReport;
use App\Models\ExpenseTransaction;
use App\Models\KpiTarget;
use App\Models\SalesProjection;
use App\Models\Store;
use App\Models\ThirdPartyStatement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Phase 4 — QA: multi-tenant security audit.
 *
 * Sweeps every tenant-scoped financial model and proves that one tenant can
 * never see another tenant's rows, while the escape hatch / background context
 * still sees everything.
 */
class MultiTenantSecurityAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $ownerA;
    private User $ownerB;
    private Store $storeA;
    private Store $storeB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ownerA = User::factory()->create(['role' => 'owner']);
        $this->ownerB = User::factory()->create(['role' => 'owner']);
        $this->storeA = Store::factory()->create(['created_by' => $this->ownerA->id]);
        $this->storeB = Store::factory()->create(['created_by' => $this->ownerB->id]);

        $this->seedForBothTenants();
    }

    private function seedForBothTenants(): void
    {
        foreach ([$this->storeA, $this->storeB] as $i => $store) {
            ExpenseTransaction::factory()->create(['store_id' => $store->id]);
            DailyReport::factory()->create(['store_id' => $store->id, 'report_date' => Carbon::parse('2026-06-01')->addDays($i)]);
            ThirdPartyStatement::factory()->create(['store_id' => $store->id]);
            BankAccount::factory()->create(['store_id' => $store->id]);
            KpiTarget::create(['store_id' => $store->id, 'food_cost_pct' => 10 + $i]);
            SalesProjection::create(['store_id' => $store->id, 'projection_date' => '2026-06-01', 'amount' => 100 + $i]);
        }
    }

    /**
     * @return array<string, class-string>
     */
    private function scopedModels(): array
    {
        return [
            'ExpenseTransaction' => ExpenseTransaction::class,
            'DailyReport' => DailyReport::class,
            'ThirdPartyStatement' => ThirdPartyStatement::class,
            'BankAccount' => BankAccount::class,
            'KpiTarget' => KpiTarget::class,
            'SalesProjection' => SalesProjection::class,
        ];
    }

    /** @test */
    public function no_tenant_can_see_another_tenants_records_in_any_scoped_model(): void
    {
        // Sanity: each model has data for BOTH tenants (unscoped).
        foreach ($this->scopedModels() as $name => $model) {
            $this->assertSame(2, $model::withoutTenantScope()->count(), "{$name} should have 2 rows total.");
        }

        // Owner A sees exactly their own store's row in every model.
        $this->actingAs($this->ownerA);
        foreach ($this->scopedModels() as $name => $model) {
            $rows = $model::all();
            $this->assertCount(1, $rows, "Owner A should see exactly 1 {$name}.");
            $this->assertSame($this->storeA->id, (int) $rows->first()->store_id, "Owner A saw a foreign {$name}!");
        }

        // Owner B likewise — and never store A's data.
        $this->actingAs($this->ownerB);
        foreach ($this->scopedModels() as $name => $model) {
            $rows = $model::all();
            $this->assertCount(1, $rows, "Owner B should see exactly 1 {$name}.");
            $this->assertSame($this->storeB->id, (int) $rows->first()->store_id, "Owner B saw a foreign {$name}!");
        }
    }

    /** @test */
    public function admin_and_background_context_see_all_tenants(): void
    {
        // Background (no auth — console/queue/webhooks): unscoped.
        foreach ($this->scopedModels() as $model) {
            $this->assertSame(2, $model::count());
        }

        // Admin: exempt.
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        foreach ($this->scopedModels() as $model) {
            $this->assertSame(2, $model::count());
        }
    }
}
