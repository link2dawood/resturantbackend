<?php

namespace Tests\Feature;

use App\Models\DailyReport;
use App\Models\SalesProjection;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Phase 4 — Sales Projection Calendar.
 */
class SalesProjectionCalendarTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function quick_entry_upserts_a_daily_projection(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $store = Store::factory()->create(['created_by' => $owner->id]);

        $this->actingAs($owner)->postJson(route('sales-projections.store'), [
            'store_id' => $store->id,
            'date' => '2026-06-10',
            'amount' => 1500,
        ])->assertOk()->assertJson(['ok' => true, 'amount' => 1500]);

        $this->assertTrue(
            SalesProjection::withoutTenantScope()
                ->where('store_id', $store->id)
                ->whereDate('projection_date', '2026-06-10')
                ->where('amount', 1500)
                ->exists()
        );

        // Re-entering the same day updates rather than duplicates.
        $this->actingAs($owner)->postJson(route('sales-projections.store'), [
            'store_id' => $store->id,
            'date' => '2026-06-10',
            'amount' => 1800,
        ])->assertOk();

        $this->assertSame(1, SalesProjection::withoutTenantScope()->where('store_id', $store->id)->count());
        $this->assertSame(1800.0, (float) SalesProjection::withoutTenantScope()->first()->amount);
    }

    /** @test */
    public function a_user_cannot_project_for_another_tenants_store(): void
    {
        $ownerA = User::factory()->create(['role' => 'owner']);
        $ownerB = User::factory()->create(['role' => 'owner']);
        $storeB = Store::factory()->create(['created_by' => $ownerB->id]);

        $this->actingAs($ownerA)->postJson(route('sales-projections.store'), [
            'store_id' => $storeB->id,
            'date' => '2026-06-10',
            'amount' => 100,
        ])->assertForbidden();

        $this->assertDatabaseMissing('sales_projections', ['store_id' => $storeB->id]);
    }

    /** @test */
    public function projections_are_tenant_scoped(): void
    {
        $ownerA = User::factory()->create(['role' => 'owner']);
        $ownerB = User::factory()->create(['role' => 'owner']);
        $storeA = Store::factory()->create(['created_by' => $ownerA->id]);
        $storeB = Store::factory()->create(['created_by' => $ownerB->id]);
        SalesProjection::create(['store_id' => $storeA->id, 'projection_date' => '2026-06-01', 'amount' => 1]);
        SalesProjection::create(['store_id' => $storeB->id, 'projection_date' => '2026-06-01', 'amount' => 2]);

        $this->actingAs($ownerA);
        $this->assertSame(1, SalesProjection::count());
    }

    /** @test */
    public function calendar_compares_projection_against_actual_net_sales(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $store = Store::factory()->create(['created_by' => $owner->id]);
        $day = Carbon::now()->startOfMonth()->addDays(5);

        SalesProjection::create(['store_id' => $store->id, 'projection_date' => $day->toDateString(), 'amount' => 1000]);
        DailyReport::factory()->create(['store_id' => $store->id, 'report_date' => $day, 'net_sales' => 1200]);

        $response = $this->actingAs($owner)->get(route('sales-projections.index', [
            'store_id' => $store->id,
            'month' => $day->format('Y-m'),
        ]));

        $response->assertOk();
        $totals = $response->viewData('totals');
        $this->assertSame(1000.0, $totals['projected']);
        $this->assertSame(1200.0, $totals['actual']);
        $this->assertSame(200.0, $totals['variance']);
    }

    /** @test */
    public function a_manager_with_store_access_can_enter_projections(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $manager = User::factory()->create(['role' => 'manager']);
        $store = Store::factory()->create(['created_by' => $owner->id]);
        $store->assignedManagers()->attach($manager->id);

        $this->actingAs($manager)->postJson(route('sales-projections.store'), [
            'store_id' => $store->id,
            'date' => '2026-06-12',
            'amount' => 900,
        ])->assertOk();

        $this->assertDatabaseHas('sales_projections', ['store_id' => $store->id, 'amount' => 900]);
    }
}
