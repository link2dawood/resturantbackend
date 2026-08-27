<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class InventoryWeeklyEntryTest extends TestCase
{
    use RefreshDatabase;

    /** A fixed Monday, independent of when the suite runs. */
    private function monday(): Carbon
    {
        return Carbon::parse('2026-08-03')->startOfWeek(Carbon::MONDAY);
    }

    // ── OpenInventoryWeek job ────────────────────────────────────────────────

    /** @test */
    public function the_job_opens_rows_for_active_items_and_seeds_from_the_prior_week(): void
    {
        $store = Store::factory()->create();
        $steak = InventoryItem::factory()->create(['store_id' => $store->id, 'is_active' => true]);
        $bread = InventoryItem::factory()->create(['store_id' => $store->id, 'is_active' => true]);
        InventoryItem::factory()->create(['store_id' => $store->id, 'is_active' => false]); // must be skipped

        // Prior week closed with 50 on hand for steak.
        InventoryStock::factory()->create([
            'inventory_item_id' => $steak->id, 'store_id' => $store->id,
            'week_start_date' => $this->monday()->copy()->subWeek()->toDateString(),
            'actual_ending_stock' => 50,
        ]);

        $this->artisan('inventory:open-week', ['--week' => $this->monday()->toDateString()])->assertExitCode(0);

        $this->assertSame(2, InventoryStock::whereDate('week_start_date', $this->monday()->toDateString())->count());
        $steakRow = InventoryStock::where('inventory_item_id', $steak->id)
            ->whereDate('week_start_date', $this->monday()->toDateString())->first();
        $this->assertEqualsWithDelta(50, (float) $steakRow->starting_stock, 1e-4); // seeded
        $breadRow = InventoryStock::where('inventory_item_id', $bread->id)
            ->whereDate('week_start_date', $this->monday()->toDateString())->first();
        $this->assertEqualsWithDelta(0, (float) $breadRow->starting_stock, 1e-4); // no prior → 0
    }

    /** @test */
    public function the_job_is_idempotent(): void
    {
        $store = Store::factory()->create();
        InventoryItem::factory()->create(['store_id' => $store->id, 'is_active' => true]);

        $this->artisan('inventory:open-week', ['--week' => $this->monday()->toDateString()]);
        $this->artisan('inventory:open-week', ['--week' => $this->monday()->toDateString()]);

        $this->assertSame(1, InventoryStock::whereDate('week_start_date', $this->monday()->toDateString())->count());
    }

    // ── Entry form ───────────────────────────────────────────────────────────

    private function employeeFor(Store $store): User
    {
        return User::factory()->create(['role' => 'employee', 'store_id' => $store->id]);
    }

    /** @test */
    public function an_employee_can_view_their_stores_entry_form(): void
    {
        $this->travelTo($this->monday()->copy()->setTime(9, 0));
        $store = Store::factory()->create();
        InventoryItem::factory()->create(['store_id' => $store->id, 'name' => 'Ribeye Steak', 'is_active' => true]);
        $employee = $this->employeeFor($store);

        $this->actingAs($employee)->get('/inventory')
            ->assertOk()
            ->assertSee('Ribeye Steak');
    }

    /** @test */
    public function submitting_stores_the_count_and_closes_the_prior_week(): void
    {
        $this->travelTo($this->monday()->copy()->setTime(9, 0));
        $store = Store::factory()->create();
        $item = InventoryItem::factory()->create(['store_id' => $store->id, 'is_active' => true]);
        $employee = $this->employeeFor($store);

        // Prior week open (ending not yet counted), current week row present.
        $prior = InventoryStock::factory()->create([
            'inventory_item_id' => $item->id, 'store_id' => $store->id,
            'week_start_date' => $this->monday()->copy()->subWeek()->toDateString(),
            'actual_ending_stock' => null,
        ]);
        $current = InventoryStock::factory()->create([
            'inventory_item_id' => $item->id, 'store_id' => $store->id,
            'week_start_date' => $this->monday()->toDateString(),
            'starting_stock' => 0, 'status' => 'draft',
        ]);

        $this->actingAs($employee)->post('/inventory/submit', [
            'store_id' => $store->id,
            'counts' => [$current->id => 120],
        ])->assertSessionHasNoErrors();

        $current->refresh();
        $this->assertEqualsWithDelta(120, (float) $current->starting_stock, 1e-4);
        $this->assertSame('submitted', $current->status);
        $this->assertSame($employee->id, $current->counted_by);

        // Prior week's ending back-filled from the same count.
        $this->assertEqualsWithDelta(120, (float) $prior->refresh()->actual_ending_stock, 1e-4);
    }

    /** @test */
    public function an_employee_is_locked_out_after_the_monday_cutoff(): void
    {
        // Two days past the week's Monday → past the EOD cutoff.
        $this->travelTo($this->monday()->copy()->addDays(2)->setTime(9, 0));
        $store = Store::factory()->create();
        $item = InventoryItem::factory()->create(['store_id' => $store->id, 'is_active' => true]);
        $employee = $this->employeeFor($store);
        $row = InventoryStock::factory()->create([
            'inventory_item_id' => $item->id, 'store_id' => $store->id,
            'week_start_date' => $this->monday()->toDateString(), 'status' => 'draft',
        ]);

        $this->actingAs($employee)->get('/inventory')->assertOk()->assertSee('Closed');

        $this->actingAs($employee)->post('/inventory/submit', [
            'store_id' => $store->id, 'counts' => [$row->id => 99],
        ])->assertStatus(403);
    }

    /** @test */
    public function an_employee_cannot_reach_manager_only_modules(): void
    {
        $store = Store::factory()->create();
        $employee = $this->employeeFor($store);

        // Employees are inventory-only; a manager/owner route must be forbidden.
        $this->actingAs($employee)->get('/managers')->assertStatus(403);
    }
}
