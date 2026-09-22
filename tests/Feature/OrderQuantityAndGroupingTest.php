<?php

namespace Tests\Feature;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\Store;
use App\Models\StoreInventoryTarget;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Phase 5 Part 2, from the 2026-09-22 client meeting: order quantities picked
 * from a whole-number list, related items kept together, and the owner off the
 * count screen.
 */
class OrderQuantityAndGroupingTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    private User $owner;

    private User $manager;

    private function monday(): Carbon
    {
        return Carbon::parse('2026-08-03')->startOfWeek(Carbon::MONDAY);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->travelTo($this->monday()->copy()->setTime(9, 0));

        $admin = User::factory()->create(['role' => 'admin']);
        $this->store = Store::factory()->create(['created_by' => $admin->id, 'store_info' => 'Round Rock']);
        $this->owner = User::factory()->create(['role' => 'owner']);
        $this->owner->ownedStores()->attach($this->store->id);
        $this->manager = User::factory()->create(['role' => 'manager', 'store_id' => $this->store->id]);
        $this->manager->assignedStoresPivot()->attach($this->store->id);
    }

    private function item(string $name, string $category, array $overrides = []): InventoryItem
    {
        return InventoryItem::factory()->create(array_merge([
            'store_id' => $this->store->id,
            'inventory_category_id' => InventoryCategory::where('name', $category)->value('id'),
            'name' => $name,
            'base_unit' => 'each',
            'purchase_unit' => 'case',
            'units_per_purchase' => 1,
            'is_active' => true,
        ], $overrides));
    }

    private function countedTo(InventoryItem $item, float $onHand, float $target): void
    {
        StoreInventoryTarget::create([
            'store_id' => $this->store->id,
            'inventory_item_id' => $item->id,
            'target_stock_level' => $target,
        ]);

        InventoryStock::updateOrCreate(
            ['inventory_item_id' => $item->id, 'week_start_date' => $this->monday()->toDateString()],
            [
                'store_id' => $this->store->id,
                'starting_stock' => $onHand,
                'status' => InventoryStock::STATUS_SUBMITTED,
                'counted_at' => now(),
            ]
        );
    }

    /** @test */
    public function order_quantities_are_a_whole_number_dropdown_not_a_decimal_box(): void
    {
        $sauce = $this->item('Marinara', 'Canned Goods & Misc', ['order_quantity_max' => 5]);
        $this->countedTo($sauce, 1, 4);

        $page = $this->actingAs($this->owner)
            ->get(route('inventory.weekly-count.suggestions', ['store_id' => $this->store->id]))
            ->assertOk();

        $page->assertSee('<select class="form-select qty-input"', false);
        $page->assertDontSee('<input type="number" inputmode="decimal" step="0.01" min="0"'."\n".'                                               class="form-control qty-input"', false);

        // 0 to 5 for an ordinary item, and nothing above it.
        for ($q = 0; $q <= 5; $q++) {
            $page->assertSee('<option value="'.$q.'"', false);
        }
        $page->assertDontSee('<option value="6"', false);
    }

    /** @test */
    public function bread_counts_to_ten_and_steak_to_twenty(): void
    {
        $bread = $this->item('8" Bread', 'Breads', ['order_quantity_max' => 10, 'units_per_purchase' => 60]);
        $this->countedTo($bread, 0, 1);

        $this->actingAs($this->owner)
            ->get(route('inventory.weekly-count.suggestions', ['store_id' => $this->store->id]))
            ->assertOk()
            ->assertSee('<option value="10"', false)
            ->assertDontSee('<option value="11"', false);

        $steak = $this->item('Steak', 'Meats', ['order_quantity_max' => 20, 'units_per_purchase' => 53]);
        $this->countedTo($steak, 0, 1);

        $this->actingAs($this->owner)
            ->get(route('inventory.weekly-count.suggestions', ['store_id' => $this->store->id]))
            ->assertOk()
            ->assertSee('<option value="20"', false)
            ->assertDontSee('<option value="21"', false);
    }

    /** @test */
    public function a_suggestion_above_the_ceiling_is_still_selectable(): void
    {
        // Target 9, nothing on hand, so the engine suggests 9 for an item whose
        // list normally stops at 5. Truncating would make the suggested order
        // impossible to accept.
        $sauce = $this->item('Marinara', 'Canned Goods & Misc', ['order_quantity_max' => 5]);
        $this->countedTo($sauce, 0, 9);

        $this->actingAs($this->owner)
            ->get(route('inventory.weekly-count.suggestions', ['store_id' => $this->store->id]))
            ->assertOk()
            ->assertSee('<option value="9" selected', false);
    }

    /** @test */
    public function related_items_are_kept_together_with_a_heading(): void
    {
        // Named to prove the grouping beats the alphabet: without it, "Butter"
        // would sit between the two breads.
        $this->item('Eight Inch Bread', 'Breads', ['item_group' => 'Bread and Wraps']);
        $this->item('Ten Inch Bread', 'Breads', ['item_group' => 'Bread and Wraps']);
        $this->item('Butter Spread', 'Breads');

        $html = $this->actingAs($this->manager)
            ->get(route('inventory.weekly-count.index', ['store_id' => $this->store->id]))
            ->assertOk()
            ->assertSee('Bread and Wraps', false)
            ->getContent();

        $ten = strpos($html, 'Ten Inch Bread');
        $eight = strpos($html, 'Eight Inch Bread');
        $butter = strpos($html, 'Butter Spread');

        $this->assertNotFalse($ten);
        $this->assertLessThan($butter, $ten, 'Grouped breads must come before the ungrouped item.');
        $this->assertLessThan($butter, $eight, 'Both breads belong in the group run.');

        // The heading prints once for the run, not per row.
        $this->assertSame(1, substr_count($html, 'class="item-group-heading"'));
    }

    /** @test */
    public function the_owner_is_not_offered_the_count_screens(): void
    {
        $this->item('Marinara', 'Canned Goods & Misc');

        // The client: the owner just sees the final order.
        // Match the exact href: /inventory is a prefix of /inventory-items and
        // /inventory-dashboard, which the owner does still get.
        $countHref = 'href="'.route('inventory.weekly-count.index').'"';
        $entryHref = 'href="'.route('inventory.entry.index').'"';

        $ownerPage = $this->actingAs($this->owner)->get(route('admin.orders.index'))->assertOk();
        $ownerPage->assertDontSee($countHref, false);
        $ownerPage->assertDontSee($entryHref, false);

        // The manager still has both.
        $managerPage = $this->actingAs($this->manager)->get(route('admin.orders.index'))->assertOk();
        $managerPage->assertSee($countHref, false);
        $managerPage->assertSee($entryHref, false);
    }
}
