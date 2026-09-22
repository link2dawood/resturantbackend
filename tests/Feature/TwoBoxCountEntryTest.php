<?php

namespace Tests\Feature;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\Store;
use App\Models\User;
use App\Services\Inventory\CountEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Phase 5 Part 2 — counting as whole units plus a partial.
 *
 * The client: "2 sticks provolone + 0.5", "2 cases of steak + partial pieces,
 * with validation". A pack size turns the partial into loose pieces; without
 * one it stays a quarter of a unit.
 */
class TwoBoxCountEntryTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

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
        $this->manager = User::factory()->create(['role' => 'manager', 'store_id' => $this->store->id]);
        $this->manager->assignedStoresPivot()->attach($this->store->id);
    }

    private function item(string $name, string $base, string $purchase, float $perPurchase): InventoryItem
    {
        return InventoryItem::factory()->create([
            'store_id' => $this->store->id,
            'inventory_category_id' => InventoryCategory::where('name', 'Meats')->value('id'),
            'name' => $name,
            'base_unit' => $base,
            'purchase_unit' => $purchase,
            'units_per_purchase' => $perPurchase,
            'is_active' => true,
        ]);
    }

    private function rowFor(InventoryItem $item): InventoryStock
    {
        $this->actingAs($this->manager)->get(route('inventory.weekly-count.index'))->assertOk();

        return InventoryStock::where('inventory_item_id', $item->id)->firstOrFail();
    }

    private function save(array $whole, array $partial)
    {
        return $this->actingAs($this->manager)->postJson(route('inventory.weekly-count.autosave'), [
            'store_id' => $this->store->id,
            'week' => $this->monday()->toDateString(),
            'whole' => $whole,
            'partial' => $partial,
        ]);
    }

    /** @test */
    public function two_cases_of_steak_plus_fifteen_loose_pieces_is_121_portions(): void
    {
        $steak = $this->item('Steak', 'portion', 'box', 53);
        $row = $this->rowFor($steak);

        $this->save([$row->id => 2], [$row->id => 15])->assertOk();

        // 2 x 53 + 15
        $this->assertEqualsWithDelta(121.0, (float) $row->fresh()->starting_stock, 0.001);
    }

    /** @test */
    public function two_sticks_of_provolone_plus_a_half_is_two_and_a_half(): void
    {
        $cheese = $this->item('Provolone', 'stick', 'stick', 1);
        $row = $this->rowFor($cheese);

        $this->save([$row->id => 2], [$row->id => 0.5])->assertOk();

        $this->assertEqualsWithDelta(2.5, (float) $row->fresh()->starting_stock, 0.001);
    }

    /** @test */
    public function loose_pieces_cannot_reach_a_full_pack(): void
    {
        $steak = $this->item('Steak', 'portion', 'box', 53);
        $row = $this->rowFor($steak);

        // 53 loose pieces is a whole box, so it belongs in the left column.
        $this->save([$row->id => 2], [$row->id => 53])
            ->assertStatus(422)
            ->assertJsonPath('errors.'.$row->id, 'Steak: partial cannot exceed 52 portions.');

        $this->assertNull($row->fresh()->counted_at, 'A refused count must not be stored.');

        // One short of a pack is fine.
        $this->save([$row->id => 2], [$row->id => 52])->assertOk();
        $this->assertEqualsWithDelta(158.0, (float) $row->fresh()->starting_stock, 0.001);
    }

    /** @test */
    public function loose_pieces_must_be_whole_pieces(): void
    {
        $steak = $this->item('Steak', 'portion', 'box', 53);
        $row = $this->rowFor($steak);

        $this->save([$row->id => 1], [$row->id => 2.5])
            ->assertStatus(422)
            ->assertJsonPath('errors.'.$row->id, 'Steak: loose pieces must be a whole number.');
    }

    /** @test */
    public function a_fraction_cannot_exceed_three_quarters(): void
    {
        $oil = $this->item('Frying Oil', 'jug', 'jug', 1);
        $row = $this->rowFor($oil);

        $this->save([$row->id => 1], [$row->id => 1.5])->assertStatus(422);
        $this->assertNull($row->fresh()->counted_at);
    }

    /** @test */
    public function both_boxes_empty_means_not_counted_but_zero_means_none_on_hand(): void
    {
        $steak = $this->item('Steak', 'portion', 'box', 53);
        $row = $this->rowFor($steak);

        $this->save([$row->id => null], [$row->id => null])->assertOk();
        $this->assertNull($row->fresh()->counted_at, 'Leaving a line blank is not a count of zero.');

        $this->save([$row->id => 0], [$row->id => null])->assertOk();
        $this->assertNotNull($row->fresh()->counted_at, 'A typed zero is a real count.');
        $this->assertEqualsWithDelta(0.0, (float) $row->fresh()->starting_stock, 0.001);
    }

    /** @test */
    public function reopening_the_screen_shows_the_two_boxes_as_they_were_typed(): void
    {
        $steak = $this->item('Steak', 'portion', 'box', 53);
        $row = $this->rowFor($steak);
        $this->save([$row->id => 2], [$row->id => 15])->assertOk();

        $split = CountEntry::split($steak->fresh(), (float) $row->fresh()->starting_stock);

        $this->assertEqualsWithDelta(2.0, $split['whole'], 0.001);
        $this->assertEqualsWithDelta(15.0, $split['partial'], 0.001);

        $this->actingAs($this->manager)->get(route('inventory.weekly-count.index'))
            ->assertOk()
            ->assertSee('name="whole['.$row->id.']"', false)
            ->assertSee('name="partial['.$row->id.']"', false);
    }

    /** @test */
    public function the_screen_offers_pieces_for_packs_and_quarter_buttons_otherwise(): void
    {
        $steak = $this->item('Steak', 'portion', 'box', 53);
        $oil = $this->item('Frying Oil', 'jug', 'jug', 1);
        $this->rowFor($steak);

        $page = $this->actingAs($this->manager)->get(route('inventory.weekly-count.index'))->assertOk();

        // Steak: a numeric pieces box capped one short of a pack.
        $steakRow = InventoryStock::where('inventory_item_id', $steak->id)->firstOrFail();
        $page->assertSee('data-max-partial="52"', false);
        $page->assertSee('id="partial-'.$steakRow->id.'"', false);

        // Oil: quarters to tap, no keyboard.
        $oilRow = InventoryStock::where('inventory_item_id', $oil->id)->firstOrFail();
        $page->assertSee('onclick="setFraction('.$oilRow->id.", '0.75'", false);
    }

    /** @test */
    public function the_older_single_box_payload_still_works(): void
    {
        // Anything still posting one number in purchase units keeps working.
        $steak = $this->item('Steak', 'portion', 'box', 53);
        $row = $this->rowFor($steak);

        $this->actingAs($this->manager)->postJson(route('inventory.weekly-count.autosave'), [
            'store_id' => $this->store->id,
            'week' => $this->monday()->toDateString(),
            'counts' => [$row->id => 3],
        ])->assertOk();

        $this->assertEqualsWithDelta(159.0, (float) $row->fresh()->starting_stock, 0.001);
    }
}
