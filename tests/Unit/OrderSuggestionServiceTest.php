<?php

namespace Tests\Unit;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\Store;
use App\Models\StoreInventoryTarget;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Inventory\OrderSuggestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Covers every branch of OrderSuggestionService: the formula itself, both unit
 * conversions, the guards (no target, no count, over target, zero pack size),
 * rounding, ordering, and scoping.
 */
class OrderSuggestionServiceTest extends TestCase
{
    use RefreshDatabase;

    private OrderSuggestionService $service;

    private Store $store;

    private InventoryCategory $meats;

    private Carbon $week;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(OrderSuggestionService::class);
        $this->week = Carbon::parse('2026-08-03')->startOfWeek(Carbon::MONDAY);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->store = Store::factory()->create(['created_by' => $admin->id]);
        $this->meats = InventoryCategory::where('name', 'Meats')->firstOrFail();
    }

    /** An item with 53 base units per purchase unit, matching the client's steak box. */
    private function item(string $name = 'Steak', array $overrides = []): InventoryItem
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

    private function target(InventoryItem $item, float $target, ?float $min = null): StoreInventoryTarget
    {
        return StoreInventoryTarget::create([
            'store_id' => $this->store->id,
            'inventory_item_id' => $item->id,
            'target_stock_level' => $target,
            'min_stock_level' => $min,
        ]);
    }

    /** A counted week row. $baseQuantity is in the item's BASE unit. */
    private function countRow(InventoryItem $item, float $baseQuantity): InventoryStock
    {
        return InventoryStock::create([
            'inventory_item_id' => $item->id,
            'store_id' => $this->store->id,
            'week_start_date' => $this->week->toDateString(),
            'starting_stock' => $baseQuantity,
            'status' => InventoryStock::STATUS_DRAFT,
            'counted_by' => User::first()->id,
            'counted_at' => now(),
        ]);
    }

    private function first(): array
    {
        return $this->service->generateSuggestions($this->store, $this->week)->first();
    }

    // ---- The formula -------------------------------------------------------

    /** @test */
    public function it_subtracts_current_stock_from_the_target(): void
    {
        $steak = $this->item();
        $this->target($steak, 15);
        $this->countRow($steak, 53 * 6); // exactly 6 boxes on hand

        $row = $this->first();

        $this->assertSame(6.0, $row['current_stock']);
        $this->assertSame(15.0, $row['target_stock']);
        $this->assertSame(9.0, $row['suggested_order']); // 15 - 6
        $this->assertTrue($row['needs_order']);
    }

    /** @test */
    public function an_empty_shelf_suggests_the_whole_target(): void
    {
        $steak = $this->item();
        $this->target($steak, 15);
        $this->countRow($steak, 0);

        $row = $this->first();

        $this->assertSame(0.0, $row['current_stock']);
        $this->assertSame(15.0, $row['suggested_order']);
        $this->assertTrue($row['is_counted']);
    }

    /** @test */
    public function being_exactly_on_target_suggests_nothing(): void
    {
        $steak = $this->item();
        $this->target($steak, 15);
        $this->countRow($steak, 53 * 15);

        $row = $this->first();

        $this->assertSame(15.0, $row['current_stock']);
        $this->assertSame(0.0, $row['suggested_order']);
        $this->assertFalse($row['needs_order']);
    }

    /** @test */
    public function being_over_target_never_suggests_a_negative_order(): void
    {
        $steak = $this->item();
        $this->target($steak, 15);
        $this->countRow($steak, 53 * 22); // 7 boxes over

        $row = $this->first();

        $this->assertSame(22.0, $row['current_stock']);
        $this->assertSame(0.0, $row['suggested_order']);
        $this->assertSame(0.0, $row['suggested_order_exact']);
        $this->assertFalse($row['needs_order']);
    }

    // ---- Unit conversion ---------------------------------------------------

    /** @test */
    public function the_count_is_converted_from_base_units_to_purchase_units(): void
    {
        // 424 portions on hand, 53 per box = 8 boxes.
        $steak = $this->item();
        $this->target($steak, 15);
        $this->countRow($steak, 424);

        $row = $this->first();

        $this->assertSame(424.0, $row['current_stock_base']);
        $this->assertSame(8.0, $row['current_stock']);
        $this->assertSame(7.0, $row['suggested_order']);
        $this->assertSame('box', $row['unit']);
    }

    /** @test */
    public function a_partial_purchase_unit_on_hand_rounds_the_order_up(): void
    {
        // 100 portions = 1.8868 boxes. Target 5, so 3.1132 boxes short.
        $steak = $this->item();
        $this->target($steak, 5);
        $this->countRow($steak, 100);

        $row = $this->first();

        $this->assertEqualsWithDelta(1.8868, $row['current_stock'], 0.0001);
        $this->assertEqualsWithDelta(3.1132, $row['suggested_order_exact'], 0.0001);
        // You cannot order 3.11 boxes.
        $this->assertSame(4.0, $row['suggested_order']);
    }

    /** @test */
    public function an_item_counted_in_its_purchase_unit_needs_no_conversion(): void
    {
        // units_per_purchase = 1, so base and purchase units are the same thing.
        $oil = $this->item('Frying Oil', [
            'base_unit' => 'jug', 'purchase_unit' => 'jug', 'units_per_purchase' => 1,
        ]);
        $this->target($oil, 10);
        $this->countRow($oil, 4);

        $row = $this->first();

        $this->assertSame(4.0, $row['current_stock']);
        $this->assertSame(6.0, $row['suggested_order']);
    }

    /** @test */
    public function a_zero_pack_size_falls_back_to_counting_in_purchase_units(): void
    {
        // A bad pack size must not divide by zero and take the page down.
        $broken = $this->item('Broken Pack Size', ['units_per_purchase' => 0]);
        $this->target($broken, 10);
        $this->countRow($broken, 3);

        $row = $this->first();

        $this->assertSame(3.0, $row['current_stock']);
        $this->assertSame(7.0, $row['suggested_order']);
    }

    // ---- Guards ------------------------------------------------------------

    /** @test */
    public function an_item_with_no_target_suggests_nothing_and_is_flagged(): void
    {
        $steak = $this->item();
        $this->countRow($steak, 53 * 2);

        $row = $this->first();

        $this->assertFalse($row['has_target']);
        $this->assertSame(0.0, $row['target_stock']);
        $this->assertSame(0.0, $row['suggested_order']);
        $this->assertFalse($row['needs_order']);
    }

    /** @test */
    public function an_uncounted_item_is_treated_as_empty_and_flagged(): void
    {
        $steak = $this->item();
        $this->target($steak, 15);
        // No count row at all.

        $row = $this->first();

        $this->assertFalse($row['is_counted']);
        $this->assertSame(0.0, $row['current_stock']);
        $this->assertSame(15.0, $row['suggested_order']);
    }

    /** @test */
    public function a_draft_count_that_was_never_entered_does_not_read_as_zero_on_hand(): void
    {
        $steak = $this->item();
        $this->target($steak, 15);

        // An opened-but-blank row: counted_at is null, so it is not a count.
        InventoryStock::create([
            'inventory_item_id' => $steak->id,
            'store_id' => $this->store->id,
            'week_start_date' => $this->week->toDateString(),
            'starting_stock' => 0,
            'status' => InventoryStock::STATUS_DRAFT,
        ]);

        $row = $this->first();

        $this->assertFalse($row['is_counted']);
        $this->assertSame(15.0, $row['suggested_order']);
    }

    /** @test */
    public function an_inactive_item_is_left_out_entirely(): void
    {
        $active = $this->item('Active Item');
        $this->target($active, 5);
        $retired = $this->item('Retired Item', ['is_active' => false]);
        $this->target($retired, 5);

        $rows = $this->service->generateSuggestions($this->store, $this->week);

        $this->assertCount(1, $rows);
        $this->assertSame('Active Item', $rows->first()['item']->name);
    }

    /** @test */
    public function a_store_with_no_items_returns_an_empty_collection(): void
    {
        $rows = $this->service->generateSuggestions($this->store, $this->week);

        $this->assertTrue($rows->isEmpty());
    }

    // ---- Vendor ------------------------------------------------------------

    /** @test */
    public function the_preferred_vendor_comes_back_with_the_suggestion(): void
    {
        $lisanti = Vendor::factory()->create(['vendor_name' => 'Lisanti']);
        $steak = $this->item('Steak', ['preferred_vendor_id' => $lisanti->id]);
        $this->target($steak, 15);
        $this->countRow($steak, 0);

        $row = $this->first();

        $this->assertNotNull($row['preferred_vendor']);
        $this->assertSame('Lisanti', $row['preferred_vendor']->vendor_name);
    }

    /** @test */
    public function an_item_with_no_preferred_vendor_returns_null_for_it(): void
    {
        $steak = $this->item();
        $this->target($steak, 15);

        $this->assertNull($this->first()['preferred_vendor']);
    }

    // ---- Week and store scoping --------------------------------------------

    /** @test */
    public function only_the_requested_weeks_count_is_used(): void
    {
        $steak = $this->item();
        $this->target($steak, 15);
        $this->countRow($steak, 53 * 6); // this week: 6 boxes

        // A different week's count must not leak in.
        InventoryStock::create([
            'inventory_item_id' => $steak->id,
            'store_id' => $this->store->id,
            'week_start_date' => $this->week->copy()->subWeek()->toDateString(),
            'starting_stock' => 53 * 14,
            'status' => InventoryStock::STATUS_DRAFT,
            'counted_at' => now(),
        ]);

        $this->assertSame(9.0, $this->first()['suggested_order']);
    }

    /** @test */
    public function a_mid_week_date_is_normalised_to_that_weeks_monday(): void
    {
        $steak = $this->item();
        $this->target($steak, 15);
        $this->countRow($steak, 53 * 6);

        $thursday = $this->week->copy()->addDays(3);
        $rows = $this->service->generateSuggestions($this->store, $thursday);

        $this->assertSame(9.0, $rows->first()['suggested_order']);
    }

    /** @test */
    public function another_stores_items_targets_and_counts_are_excluded(): void
    {
        $mine = $this->item('Mine');
        $this->target($mine, 15);
        $this->countRow($mine, 0);

        $otherStore = Store::factory()->create(['created_by' => User::first()->id]);
        $theirs = InventoryItem::factory()->create([
            'store_id' => $otherStore->id,
            'inventory_category_id' => $this->meats->id,
            'name' => 'Theirs',
        ]);
        StoreInventoryTarget::create([
            'store_id' => $otherStore->id, 'inventory_item_id' => $theirs->id, 'target_stock_level' => 99,
        ]);

        $rows = $this->service->generateSuggestions($this->store, $this->week);

        $this->assertCount(1, $rows);
        $this->assertSame('Mine', $rows->first()['item']->name);
    }

    /** @test */
    public function a_target_set_by_another_store_does_not_apply_here(): void
    {
        $steak = $this->item();
        $otherStore = Store::factory()->create(['created_by' => User::first()->id]);

        // Same item id, other store's target row. Must be ignored.
        StoreInventoryTarget::create([
            'store_id' => $otherStore->id, 'inventory_item_id' => $steak->id, 'target_stock_level' => 99,
        ]);

        $row = $this->first();

        $this->assertFalse($row['has_target']);
        $this->assertSame(0.0, $row['suggested_order']);
    }

    // ---- Ordering and helpers ----------------------------------------------

    /** @test */
    public function rows_come_back_in_order_guide_sequence(): void
    {
        $breads = InventoryCategory::where('name', 'Breads')->firstOrFail();
        $this->item('Zebra Bread', ['inventory_category_id' => $breads->id]);
        $this->item('Apple Meat');
        $this->item('Beta Meat');

        $names = $this->service->generateSuggestions($this->store, $this->week)
            ->map(fn ($row) => $row['item']->name)->all();

        // Meats (display_order 10) before Breads (20), alphabetical within.
        $this->assertSame(['Apple Meat', 'Beta Meat', 'Zebra Bread'], $names);
    }

    /** @test */
    public function orders_needed_returns_only_the_rows_that_need_ordering(): void
    {
        $short = $this->item('Short Item');
        $this->target($short, 15);
        $this->countRow($short, 0);

        $stocked = $this->item('Stocked Item');
        $this->target($stocked, 5);
        $this->countRow($stocked, 53 * 5);

        $this->item('No Target Item');

        $needed = $this->service->ordersNeeded($this->store, $this->week);

        $this->assertCount(1, $needed);
        $this->assertSame('Short Item', $needed->first()['item']->name);
    }

    /** @test */
    public function every_documented_key_is_present_on_each_row(): void
    {
        $steak = $this->item();
        $this->target($steak, 15);
        $this->countRow($steak, 0);

        $this->assertEqualsCanonicalizing([
            'item', 'current_stock', 'current_stock_base', 'target_stock',
            'suggested_order', 'suggested_order_exact', 'preferred_vendor',
            'has_target', 'is_counted', 'needs_order', 'unit',
        ], array_keys($this->first()));
    }
}
