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
            ->assertSee('1 box = 53 portion')   // pack-size hint
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
        // 120 boxes entered, stored as 120 x 53 portions.
        $this->assertEqualsWithDelta(120 * 53, (float) $row->starting_stock, 1e-4);
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
        $this->assertEqualsWithDelta(75 * 53, (float) $row->fresh()->starting_stock, 1e-4);
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

    /** @test */
    public function counts_are_entered_in_the_unit_the_item_is_ordered_in(): void
    {
        // Steak is 53 portions to a box. The manager can see 7 boxes on the
        // shelf; asking them to count 371 individual portions at 7am is not a
        // workable ask.
        $steak = $this->item('Ribeye Steak');
        $this->actingAs($this->manager)->get(route('inventory.weekly-count.index'))->assertOk();
        $row = $this->rowFor($steak);

        $this->actingAs($this->manager)->postJson(route('inventory.weekly-count.autosave'), [
            'store_id' => $this->store->id,
            'week' => $this->monday()->toDateString(),
            'counts' => [$row->id => 7],
        ])->assertOk();

        // Stored in base units so variance still works.
        $this->assertEqualsWithDelta(371, (float) $row->fresh()->starting_stock, 1e-4);
    }

    /** @test */
    public function the_count_screen_asks_for_and_shows_the_ordering_unit(): void
    {
        $steak = $this->item('Ribeye Steak');
        $this->actingAs($this->manager)->get(route('inventory.weekly-count.index'))->assertOk();
        $row = $this->rowFor($steak);
        $row->update(['starting_stock' => 371, 'counted_at' => now()]);

        $this->actingAs($this->manager)
            ->get(route('inventory.weekly-count.index'))
            ->assertOk()
            ->assertSee('How many box of Ribeye Steak are on hand', false)
            ->assertSee('1 box = 53 portion')
            ->assertSee('value="7"', false);
    }

    /** @test */
    public function last_weeks_figure_is_shown_in_the_ordering_unit_too(): void
    {
        $steak = $this->item('Ribeye Steak');

        InventoryStock::factory()->create([
            'inventory_item_id' => $steak->id,
            'store_id' => $this->store->id,
            'week_start_date' => $this->monday()->copy()->subWeek()->toDateString(),
            'starting_stock' => 53 * 4,
            'counted_at' => now()->subWeek(),
        ]);

        $this->actingAs($this->manager)
            ->get(route('inventory.weekly-count.index'))
            ->assertOk()
            ->assertSee('Last week:')
            ->assertSee('>4</strong>', false);
    }

    /** @test */
    public function a_count_in_ordering_units_produces_the_right_suggestion(): void
    {
        // The whole point: 7 boxes on hand, target 15, so order 8.
        $steak = $this->item('Ribeye Steak');
        \App\Models\StoreInventoryTarget::create([
            'store_id' => $this->store->id,
            'inventory_item_id' => $steak->id,
            'target_stock_level' => 15,
        ]);

        $this->actingAs($this->manager)->get(route('inventory.weekly-count.index'))->assertOk();
        $row = $this->rowFor($steak);

        $this->actingAs($this->manager)->postJson(route('inventory.weekly-count.autosave'), [
            'store_id' => $this->store->id,
            'week' => $this->monday()->toDateString(),
            'counts' => [$row->id => 7],
        ])->assertOk();

        $suggestion = app(\App\Services\Inventory\OrderSuggestionService::class)
            ->generateSuggestions($this->store, $this->monday())->first();

        $this->assertEqualsWithDelta(7.0, $suggestion['current_stock'], 1e-4);
        $this->assertEqualsWithDelta(8.0, $suggestion['suggested_order'], 1e-4);
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
        $this->assertEqualsWithDelta(120 * 53, (float) $row->starting_stock, 1e-4);

        // The same count closes the prior week for the variance engine.
        $this->assertEqualsWithDelta(120 * 53, (float) $prior->fresh()->actual_ending_stock, 1e-4);
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

        $this->assertEqualsWithDelta(120 * 53, (float) $row->fresh()->starting_stock, 1e-4);

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
        $this->assertEqualsWithDelta(130 * 53, (float) $row->fresh()->starting_stock, 1e-4);
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

        $this->assertEqualsWithDelta(80 * 53, (float) $row->fresh()->starting_stock, 1e-4);
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
    public function the_monday_reminder_reaches_the_bell_and_sends_no_email(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        $this->item('Ribeye Steak');

        $this->artisan('inventory:remind', ['--week' => $this->monday()->toDateString()])
            ->assertExitCode(0);

        $notification = $this->manager->fresh()->notifications()->first();

        $this->assertNotNull($notification, 'It should land on the bell.');
        $this->assertSame("Time to enter this week's inventory", $notification->data['title']);
        $this->assertStringContainsString('/inventory/weekly-count', $notification->data['url']);

        // In-app only: the client did not want an inbox full of these.
        \Illuminate\Support\Facades\Mail::assertNothingSent();
    }

    /** @test */
    public function the_monday_reminder_is_scheduled_and_the_wednesday_chase_is_not(): void
    {
        $events = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events());

        $monday = $events->filter(fn ($e) => str_contains($e->command ?? '', 'inventory:remind')
            && ! str_contains($e->command ?? '', 'overdue'));
        $this->assertCount(1, $monday);
        $this->assertSame('0 6 * * 1', $monday->first()->expression);

        // Counting happens on Monday because orders go out by Wednesday; the
        // client asked for no follow-up nagging.
        $this->assertCount(
            0,
            $events->filter(fn ($e) => str_contains($e->command ?? '', 'inventory:remind-overdue')),
            'The Wednesday chase should not be scheduled.'
        );
    }

}
