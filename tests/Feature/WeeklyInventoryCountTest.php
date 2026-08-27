<?php

namespace Tests\Feature;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\Store;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\InventoryReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class WeeklyInventoryCountTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    private User $manager;

    private InventoryCategory $meats;

    /** A fixed Monday, so the suite does not drift with the calendar. */
    private function monday(): Carbon
    {
        return Carbon::parse('2026-08-03')->startOfWeek(Carbon::MONDAY);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo($this->monday()->copy()->setTime(9, 0));

        $admin = User::factory()->create(['role' => 'admin']);
        $this->store = Store::factory()->create(['created_by' => $admin->id, 'store_info' => 'Round Rock']);
        $this->manager = User::factory()->create(['role' => 'manager', 'store_id' => $this->store->id]);
        $this->manager->assignedStoresPivot()->attach($this->store->id);
        $this->meats = InventoryCategory::where('name', 'Meats')->firstOrFail();
    }

    private function item(string $name, array $overrides = []): InventoryItem
    {
        return InventoryItem::factory()->create(array_merge([
            'store_id' => $this->store->id,
            'inventory_category_id' => $this->meats->id,
            'name' => $name,
            'base_unit' => 'portion',
            'purchase_unit' => 'box',
            'units_per_purchase' => 53,
            'is_active' => true,
        ], $overrides));
    }

    private function rowFor(InventoryItem $item, ?Carbon $week = null): InventoryStock
    {
        return InventoryStock::where('inventory_item_id', $item->id)
            ->forWeek(($week ?? $this->monday())->toDateString())
            ->firstOrFail();
    }

    // ---- The page ----------------------------------------------------------

    /** @test */
    public function a_manager_sees_this_weeks_items_grouped_in_order_guide_sequence(): void
    {
        $breads = InventoryCategory::where('name', 'Breads')->firstOrFail();
        $this->item('Ribeye Steak');
        $this->item('Pita Bread', ['inventory_category_id' => $breads->id]);

        $html = $this->actingAs($this->manager)
            ->get(route('inventory.weekly-count.index'))
            ->assertOk()
            ->assertSee('Weekly Inventory Count')
            ->assertSee('Ribeye Steak')
            ->assertSee('Pita Bread')
            ->getContent();

        // Meats (display_order 10) must render before Breads (20).
        $this->assertLessThan(strpos($html, 'Breads'), strpos($html, 'Meats'));
    }

    /** @test */
    public function opening_the_page_creates_this_weeks_rows_for_active_items_only(): void
    {
        $this->item('Ribeye Steak');
        $this->item('Retired Item', ['is_active' => false]);

        $this->actingAs($this->manager)->get(route('inventory.weekly-count.index'))->assertOk();

        $this->assertSame(1, InventoryStock::forWeek($this->monday()->toDateString())->count());
    }

    /** @test */
    public function the_page_shows_the_pack_size_hint_and_last_weeks_count(): void
    {
        $steak = $this->item('Ribeye Steak');

        InventoryStock::factory()->create([
            'inventory_item_id' => $steak->id,
            'store_id' => $this->store->id,
            'week_start_date' => $this->monday()->copy()->subWeek()->toDateString(),
            'starting_stock' => 42,
            'counted_at' => now()->subWeek(),
        ]);

        $this->actingAs($this->manager)
            ->get(route('inventory.weekly-count.index'))
            ->assertOk()
            ->assertSee('53 portion per box')   // portions_per_unit hint
            ->assertSee('Last week:')
            ->assertSee('42');
    }

    /** @test */
    public function the_progress_indicator_reports_how_many_items_are_counted(): void
    {
        $steak = $this->item('Ribeye Steak');
        $this->item('Chicken Breast');

        $this->actingAs($this->manager)->get(route('inventory.weekly-count.index'))->assertOk();

        $this->rowFor($steak)->update(['starting_stock' => 10, 'counted_at' => now()]);

        $this->actingAs($this->manager)
            ->get(route('inventory.weekly-count.index'))
            ->assertOk()
            ->assertSee('1 of 2 items counted');
    }

    /** @test */
    public function the_layout_can_be_grouped_by_vendor_like_the_paper_order_guide(): void
    {
        $lisanti = Vendor::factory()->create(['vendor_name' => 'Lisanti']);
        $this->item('Ribeye Steak', ['preferred_vendor_id' => $lisanti->id]);
        $this->item('Unassigned Item');

        $this->actingAs($this->manager)
            ->get(route('inventory.weekly-count.index', ['group_by' => 'vendor']))
            ->assertOk()
            ->assertSee('Lisanti')
            ->assertSee('No vendor assigned');
    }

    // ---- Auto-save ---------------------------------------------------------

    /** @test */
    public function autosave_stores_a_draft_without_submitting_the_week(): void
    {
        $steak = $this->item('Ribeye Steak');
        $this->actingAs($this->manager)->get(route('inventory.weekly-count.index'))->assertOk();
        $row = $this->rowFor($steak);

        $this->actingAs($this->manager)
            ->postJson(route('inventory.weekly-count.autosave'), [
                'store_id' => $this->store->id,
                'week' => $this->monday()->toDateString(),
                'counts' => [$row->id => 120],
            ])
            ->assertOk()
            ->assertJsonPath('counted_items', 1)
            ->assertJsonPath('total_items', 1);

        $row->refresh();
        $this->assertEqualsWithDelta(120, (float) $row->starting_stock, 1e-4);
        $this->assertSame(InventoryStock::STATUS_DRAFT, $row->status);
        $this->assertFalse($row->is_submitted);
        $this->assertSame($this->manager->id, $row->counted_by);
        $this->assertNotNull($row->counted_at);
    }

    /** @test */
    public function autosave_is_idempotent_and_can_run_repeatedly(): void
    {
        $steak = $this->item('Ribeye Steak');
        $this->actingAs($this->manager)->get(route('inventory.weekly-count.index'))->assertOk();
        $row = $this->rowFor($steak);

        foreach ([50, 50, 75] as $value) {
            $this->actingAs($this->manager)->postJson(route('inventory.weekly-count.autosave'), [
                'store_id' => $this->store->id,
                'week' => $this->monday()->toDateString(),
                'counts' => [$row->id => $value],
            ])->assertOk();
        }

        $this->assertSame(1, InventoryStock::where('inventory_item_id', $steak->id)
            ->forWeek($this->monday()->toDateString())->count());
        $this->assertEqualsWithDelta(75, (float) $row->fresh()->starting_stock, 1e-4);
    }

    /** @test */
    public function a_blank_input_is_left_uncounted_rather_than_saved_as_zero(): void
    {
        $steak = $this->item('Ribeye Steak');
        $this->actingAs($this->manager)->get(route('inventory.weekly-count.index'))->assertOk();
        $row = $this->rowFor($steak);

        // "not counted yet" and "counted, none on hand" are different facts.
        $this->actingAs($this->manager)->postJson(route('inventory.weekly-count.autosave'), [
            'store_id' => $this->store->id,
            'week' => $this->monday()->toDateString(),
            'counts' => [$row->id => null],
        ])->assertOk()->assertJsonPath('counted_items', 0);

        $this->assertNull($row->fresh()->counted_at);
    }

    /** @test */
    public function an_explicit_zero_does_count_as_counted(): void
    {
        $steak = $this->item('Ribeye Steak');
        $this->actingAs($this->manager)->get(route('inventory.weekly-count.index'))->assertOk();
        $row = $this->rowFor($steak);

        $this->actingAs($this->manager)->postJson(route('inventory.weekly-count.autosave'), [
            'store_id' => $this->store->id,
            'week' => $this->monday()->toDateString(),
            'counts' => [$row->id => 0],
        ])->assertOk()->assertJsonPath('counted_items', 1);

        $this->assertNotNull($row->fresh()->counted_at);
        $this->assertEqualsWithDelta(0, (float) $row->fresh()->starting_stock, 1e-4);
    }

    /** @test */
    public function a_note_can_be_saved_against_a_line(): void
    {
        $steak = $this->item('Ribeye Steak');
        $this->actingAs($this->manager)->get(route('inventory.weekly-count.index'))->assertOk();
        $row = $this->rowFor($steak);

        $this->actingAs($this->manager)->postJson(route('inventory.weekly-count.autosave'), [
            'store_id' => $this->store->id,
            'week' => $this->monday()->toDateString(),
            'counts' => [$row->id => 10],
            'notes' => [$row->id => '2 boxes damaged, not counted'],
        ])->assertOk();

        $this->assertSame('2 boxes damaged, not counted', $row->fresh()->notes);
    }

    /** @test */
    public function a_negative_count_is_rejected(): void
    {
        $steak = $this->item('Ribeye Steak');
        $this->actingAs($this->manager)->get(route('inventory.weekly-count.index'))->assertOk();
        $row = $this->rowFor($steak);

        $this->actingAs($this->manager)->postJson(route('inventory.weekly-count.autosave'), [
            'store_id' => $this->store->id,
            'week' => $this->monday()->toDateString(),
            'counts' => [$row->id => -5],
        ])->assertStatus(422);

        $this->assertNull($row->fresh()->counted_at);
    }

    // ---- Submit and lock ---------------------------------------------------

    /** @test */
    public function submitting_locks_the_week_and_closes_the_prior_week(): void
    {
        $steak = $this->item('Ribeye Steak');

        $prior = InventoryStock::factory()->create([
            'inventory_item_id' => $steak->id,
            'store_id' => $this->store->id,
            'week_start_date' => $this->monday()->copy()->subWeek()->toDateString(),
            'actual_ending_stock' => null,
        ]);

        $this->actingAs($this->manager)->get(route('inventory.weekly-count.index'))->assertOk();
        $row = $this->rowFor($steak);

        $this->actingAs($this->manager)->post(route('inventory.weekly-count.submit'), [
            'store_id' => $this->store->id,
            'week' => $this->monday()->toDateString(),
            'counts' => [$row->id => 120],
        ])->assertRedirect();

        $row->refresh();
        $this->assertSame(InventoryStock::STATUS_SUBMITTED, $row->status);
        $this->assertTrue($row->is_submitted);
        $this->assertEqualsWithDelta(120, (float) $row->starting_stock, 1e-4);

        // The same count closes the prior week for the variance engine.
        $this->assertEqualsWithDelta(120, (float) $prior->fresh()->actual_ending_stock, 1e-4);
    }

    /** @test */
    public function a_submitted_week_is_read_only(): void
    {
        $steak = $this->item('Ribeye Steak');
        $this->actingAs($this->manager)->get(route('inventory.weekly-count.index'))->assertOk();
        $row = $this->rowFor($steak);

        $this->actingAs($this->manager)->post(route('inventory.weekly-count.submit'), [
            'store_id' => $this->store->id,
            'week' => $this->monday()->toDateString(),
            'counts' => [$row->id => 120],
        ])->assertRedirect();

        // Autosave is refused...
        $this->actingAs($this->manager)->postJson(route('inventory.weekly-count.autosave'), [
            'store_id' => $this->store->id,
            'week' => $this->monday()->toDateString(),
            'counts' => [$row->id => 999],
        ])->assertStatus(422);

        // ...and so is a second submit.
        $this->actingAs($this->manager)->post(route('inventory.weekly-count.submit'), [
            'store_id' => $this->store->id,
            'week' => $this->monday()->toDateString(),
            'counts' => [$row->id => 999],
        ])->assertSessionHas('error');

        $this->assertEqualsWithDelta(120, (float) $row->fresh()->starting_stock, 1e-4);

        $this->actingAs($this->manager)
            ->get(route('inventory.weekly-count.index'))
            ->assertOk()
            ->assertSee('submitted and locked');
    }

    /** @test */
    public function the_submit_message_reports_items_left_uncounted(): void
    {
        $steak = $this->item('Ribeye Steak');
        $this->item('Chicken Breast');
        $this->actingAs($this->manager)->get(route('inventory.weekly-count.index'))->assertOk();

        $this->actingAs($this->manager)->post(route('inventory.weekly-count.submit'), [
            'store_id' => $this->store->id,
            'week' => $this->monday()->toDateString(),
            'counts' => [$this->rowFor($steak)->id => 120],
        ])->assertSessionHas('success', fn ($m) => str_contains($m, '1 item(s) were left uncounted'));
    }

    /** @test */
    public function only_an_admin_can_unlock_a_submitted_week(): void
    {
        $steak = $this->item('Ribeye Steak');
        $this->actingAs($this->manager)->get(route('inventory.weekly-count.index'))->assertOk();
        $row = $this->rowFor($steak);

        $this->actingAs($this->manager)->post(route('inventory.weekly-count.submit'), [
            'store_id' => $this->store->id,
            'week' => $this->monday()->toDateString(),
            'counts' => [$row->id => 120],
        ])->assertRedirect();

        $unlockPayload = ['store_id' => $this->store->id, 'week' => $this->monday()->toDateString()];

        $this->actingAs($this->manager)
            ->post(route('inventory.weekly-count.unlock'), $unlockPayload)
            ->assertForbidden();
        $this->assertSame(InventoryStock::STATUS_SUBMITTED, $row->fresh()->status);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post(route('inventory.weekly-count.unlock'), $unlockPayload)->assertRedirect();
        $this->assertSame(InventoryStock::STATUS_DRAFT, $row->fresh()->status);

        // And editing works again afterwards.
        $this->actingAs($this->manager)->postJson(route('inventory.weekly-count.autosave'), [
            'store_id' => $this->store->id,
            'week' => $this->monday()->toDateString(),
            'counts' => [$row->id => 130],
        ])->assertOk();
        $this->assertEqualsWithDelta(130, (float) $row->fresh()->starting_stock, 1e-4);
    }

    // ---- Week rules --------------------------------------------------------

    /** @test */
    public function a_future_week_cannot_be_counted(): void
    {
        $steak = $this->item('Ribeye Steak');
        $this->actingAs($this->manager)->get(route('inventory.weekly-count.index'))->assertOk();
        $row = $this->rowFor($steak);
        $nextWeek = $this->monday()->copy()->addWeek()->toDateString();

        $this->actingAs($this->manager)
            ->get(route('inventory.weekly-count.index', ['week' => $nextWeek]))
            ->assertOk()
            ->assertSee('has not started yet');

        $this->actingAs($this->manager)->postJson(route('inventory.weekly-count.autosave'), [
            'store_id' => $this->store->id, 'week' => $nextWeek, 'counts' => [$row->id => 99],
        ])->assertStatus(422);

        $this->actingAs($this->manager)->post(route('inventory.weekly-count.submit'), [
            'store_id' => $this->store->id, 'week' => $nextWeek, 'counts' => [$row->id => 99],
        ])->assertSessionHas('error');

        // No rows are opened for a week that has not started.
        $this->assertSame(0, InventoryStock::forWeek($nextWeek)->count());
    }

    /** @test */
    public function a_past_week_stays_editable_until_it_is_submitted(): void
    {
        $steak = $this->item('Ribeye Steak');
        $lastWeek = $this->monday()->copy()->subWeek()->toDateString();

        $this->actingAs($this->manager)
            ->get(route('inventory.weekly-count.index', ['week' => $lastWeek]))
            ->assertOk();

        $row = $this->rowFor($steak, $this->monday()->copy()->subWeek());

        $this->actingAs($this->manager)->postJson(route('inventory.weekly-count.autosave'), [
            'store_id' => $this->store->id, 'week' => $lastWeek, 'counts' => [$row->id => 80],
        ])->assertOk();

        $this->assertEqualsWithDelta(80, (float) $row->fresh()->starting_stock, 1e-4);
    }

    /** @test */
    public function the_week_defaults_to_the_current_monday_to_sunday(): void
    {
        // Mid-week, the page must still land on this week's Monday.
        $this->travelTo($this->monday()->copy()->addDays(3)->setTime(14, 0));
        $this->item('Ribeye Steak');

        $this->actingAs($this->manager)
            ->get(route('inventory.weekly-count.index'))
            ->assertOk()
            ->assertSee('Aug 3 – Aug 9, 2026');
    }

    // ---- Store scoping -----------------------------------------------------

    /** @test */
    public function a_manager_only_ever_sees_their_own_store(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $otherStore = Store::factory()->create(['created_by' => $admin->id]);
        InventoryItem::factory()->create([
            'store_id' => $otherStore->id,
            'inventory_category_id' => $this->meats->id,
            'name' => 'Omega Foreign Fixture',
        ]);
        $this->item('Zeta Mine Fixture');

        // Even asking for the other store's id falls back to their own.
        $this->actingAs($this->manager)
            ->get(route('inventory.weekly-count.index', ['store_id' => $otherStore->id]))
            ->assertOk()
            ->assertSee('Zeta Mine Fixture')
            ->assertDontSee('Omega Foreign Fixture');

        $this->assertSame(0, InventoryStock::where('store_id', $otherStore->id)->count());
    }

    /** @test */
    public function a_count_posted_for_another_stores_row_is_ignored(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $otherStore = Store::factory()->create(['created_by' => $admin->id]);
        $foreignItem = InventoryItem::factory()->create([
            'store_id' => $otherStore->id, 'inventory_category_id' => $this->meats->id,
        ]);
        $foreignRow = InventoryStock::factory()->create([
            'inventory_item_id' => $foreignItem->id,
            'store_id' => $otherStore->id,
            'week_start_date' => $this->monday()->toDateString(),
            'starting_stock' => 5,
        ]);

        $mine = $this->item('Ribeye Steak');
        $this->actingAs($this->manager)->get(route('inventory.weekly-count.index'))->assertOk();

        $this->actingAs($this->manager)->postJson(route('inventory.weekly-count.autosave'), [
            'store_id' => $this->store->id,
            'week' => $this->monday()->toDateString(),
            'counts' => [$this->rowFor($mine)->id => 10, $foreignRow->id => 999],
        ])->assertOk();

        $this->assertEqualsWithDelta(5, (float) $foreignRow->fresh()->starting_stock, 1e-4);
    }

    /** @test */
    public function a_guest_is_redirected_to_login(): void
    {
        $this->get(route('inventory.weekly-count.index'))->assertRedirect(route('login'));
    }

    // ---- Monday reminder ---------------------------------------------------

    /** @test */
    public function the_monday_reminder_emails_the_manager_and_links_to_the_count(): void
    {
        Notification::fake();
        $this->item('Ribeye Steak');

        $this->artisan('inventory:remind', ['--week' => $this->monday()->toDateString()])
            ->assertExitCode(0);

        Notification::assertSentTo($this->manager, InventoryReminderNotification::class,
            function (InventoryReminderNotification $notification) {
                $mail = $notification->toMail($this->manager);

                $this->assertStringContainsString("Time to enter this week's inventory", $mail->subject);
                $this->assertStringContainsString('/inventory/weekly-count', $mail->actionUrl);

                return true;
            });
    }

    /** @test */
    public function the_reminder_is_scheduled_for_monday_at_six_am(): void
    {
        // Exclude inventory:remind-overdue, which is the Wednesday chase and
        // also contains the substring "inventory:remind".
        $events = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())
            ->filter(fn ($event) => str_contains($event->command ?? '', 'inventory:remind')
                && ! str_contains($event->command ?? '', 'overdue'));

        $this->assertCount(1, $events, 'The Monday reminder should be scheduled exactly once.');
        // "0 6 * * 1" = 06:00 every Monday.
        $this->assertSame('0 6 * * 1', $events->first()->expression);
    }
}
