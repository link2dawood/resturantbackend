<?php

namespace Tests\Feature;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Store;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorPrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemVendorMappingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Store $store;

    private InventoryCategory $meats;

    private Vendor $lisanti;

    private Vendor $depot;

    private Vendor $sams;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->store = Store::factory()->create(['created_by' => $this->admin->id]);
        $this->meats = InventoryCategory::where('name', 'Meats')->firstOrFail();

        $this->lisanti = Vendor::factory()->create(['vendor_name' => 'Lisanti', 'vendor_type' => 'Food']);
        $this->depot = Vendor::factory()->create(['vendor_name' => 'Restaurant Depot', 'vendor_type' => 'Food']);
        $this->sams = Vendor::factory()->create(['vendor_name' => "Sam's Club", 'vendor_type' => 'Food']);
    }

    private function item(array $overrides = []): InventoryItem
    {
        return InventoryItem::factory()->create(array_merge([
            'store_id' => $this->store->id,
            'inventory_category_id' => $this->meats->id,
            'name' => 'Frying Oil',
            'purchase_unit' => 'jug',
            'base_unit' => 'lb',
            'units_per_purchase' => 35,
        ], $overrides));
    }

    private function payload(InventoryItem $item, array $vendors): array
    {
        return [
            'name' => $item->name,
            'inventory_category_id' => $this->meats->id,
            'purchase_unit' => $item->purchase_unit,
            'base_unit' => $item->base_unit,
            'units_per_purchase' => $item->units_per_purchase,
            'vendors' => $vendors,
        ];
    }

    private function pivotFor(InventoryItem $item, Vendor $vendor)
    {
        return $item->fresh()->vendors()->where('vendors.id', $vendor->id)->first()?->pivot;
    }

    // ---- Assigning multiple vendors ---------------------------------------

    /** @test */
    public function an_item_can_be_assigned_to_several_vendors_with_sku_and_price(): void
    {
        $item = $this->item();

        $this->actingAs($this->admin)->putJson(route('admin.inventory-items.update', $item), $this->payload($item, [
            $this->sams->id => ['enabled' => true, 'vendor_sku' => 'SC-OIL-35', 'current_price' => 42.00, 'is_preferred' => true],
            $this->lisanti->id => ['enabled' => true, 'vendor_sku' => 'LIS-OIL-35', 'current_price' => 46.50, 'is_preferred' => false],
            $this->depot->id => ['enabled' => true, 'vendor_sku' => 'RD-OIL-35', 'current_price' => 43.75, 'is_preferred' => false],
        ]))->assertOk();

        $this->assertCount(3, $item->fresh()->vendors);

        $sams = $this->pivotFor($item, $this->sams);
        $this->assertSame('SC-OIL-35', $sams->vendor_sku);
        $this->assertEquals(42.00, (float) $sams->current_price);
        $this->assertNotNull($sams->price_updated_at);
        $this->assertEquals(46.50, (float) $this->pivotFor($item, $this->lisanti)->current_price);
    }

    /** @test */
    public function unticking_a_vendor_removes_the_mapping(): void
    {
        $item = $this->item();
        $item->vendors()->attach([$this->lisanti->id, $this->depot->id]);

        $this->actingAs($this->admin)->putJson(route('admin.inventory-items.update', $item), $this->payload($item, [
            $this->lisanti->id => ['enabled' => true, 'is_preferred' => false],
            $this->depot->id => ['enabled' => false, 'is_preferred' => false],
        ]))->assertOk();

        $this->assertSame([$this->lisanti->id], $item->fresh()->vendors()->pluck('vendors.id')->all());
    }

    /** @test */
    public function omitting_the_vendors_key_leaves_existing_mappings_alone(): void
    {
        $item = $this->item();
        $item->vendors()->attach($this->lisanti->id, ['is_preferred_vendor' => true]);
        $item->update(['preferred_vendor_id' => $this->lisanti->id]);

        // Editing only the pack size must not silently wipe the mappings.
        $payload = $this->payload($item, []);
        unset($payload['vendors']);
        $payload['units_per_purchase'] = 40;

        $this->actingAs($this->admin)
            ->putJson(route('admin.inventory-items.update', $item), $payload)
            ->assertOk();

        $this->assertSame([$this->lisanti->id], $item->fresh()->vendors()->pluck('vendors.id')->all());
        $this->assertSame($this->lisanti->id, $item->fresh()->preferred_vendor_id);
        $this->assertEquals(40, (float) $item->fresh()->units_per_purchase);
    }

    /** @test */
    public function an_unknown_vendor_id_is_a_validation_error(): void
    {
        $item = $this->item();

        $this->actingAs($this->admin)->putJson(route('admin.inventory-items.update', $item), $this->payload($item, [
            999999 => ['enabled' => true, 'is_preferred' => true],
        ]))->assertStatus(422)->assertJsonValidationErrors('vendors.999999');

        $this->assertCount(0, $item->fresh()->vendors);
    }

    /** @test */
    public function a_negative_price_is_rejected(): void
    {
        $item = $this->item();

        $this->actingAs($this->admin)->putJson(route('admin.inventory-items.update', $item), $this->payload($item, [
            $this->lisanti->id => ['enabled' => true, 'current_price' => -5, 'is_preferred' => true],
        ]))->assertStatus(422)->assertJsonValidationErrors('vendors.'.$this->lisanti->id.'.current_price');
    }

    // ---- The one-preferred-vendor rule ------------------------------------

    /** @test */
    public function only_one_vendor_can_be_preferred_and_switching_unsets_the_previous(): void
    {
        $item = $this->item();

        $this->actingAs($this->admin)->putJson(route('admin.inventory-items.update', $item), $this->payload($item, [
            $this->sams->id => ['enabled' => true, 'is_preferred' => true],
            $this->lisanti->id => ['enabled' => true, 'is_preferred' => false],
        ]))->assertOk();

        $this->assertSame($this->sams->id, $item->fresh()->preferred_vendor_id);

        // Now switch the preferred vendor to Lisanti.
        $this->actingAs($this->admin)->putJson(route('admin.inventory-items.update', $item), $this->payload($item, [
            $this->sams->id => ['enabled' => true, 'is_preferred' => false],
            $this->lisanti->id => ['enabled' => true, 'is_preferred' => true],
        ]))->assertOk();

        $this->assertSame($this->lisanti->id, $item->fresh()->preferred_vendor_id);
        $this->assertFalse((bool) $this->pivotFor($item, $this->sams)->is_preferred_vendor);
        $this->assertTrue((bool) $this->pivotFor($item, $this->lisanti)->is_preferred_vendor);
        $this->assertSame(
            1,
            $item->fresh()->vendors()->wherePivot('is_preferred_vendor', true)->count()
        );
    }

    /** @test */
    public function preferred_can_be_cleared_without_removing_the_vendor(): void
    {
        $item = $this->item();
        $item->vendors()->attach($this->lisanti->id, ['is_preferred_vendor' => true]);
        $item->update(['preferred_vendor_id' => $this->lisanti->id]);

        $this->actingAs($this->admin)->putJson(route('admin.inventory-items.update', $item), $this->payload($item, [
            $this->lisanti->id => ['enabled' => true, 'is_preferred' => false],
        ]))->assertOk();

        $this->assertNull($item->fresh()->preferred_vendor_id);
        $this->assertSame([$this->lisanti->id], $item->fresh()->vendors()->pluck('vendors.id')->all());
    }

    /** @test */
    public function unticking_the_preferred_vendor_clears_the_preference_too(): void
    {
        $item = $this->item();
        $item->vendors()->attach($this->lisanti->id, ['is_preferred_vendor' => true]);
        $item->update(['preferred_vendor_id' => $this->lisanti->id]);

        // A preferred vendor that no longer supplies the item would break order
        // generation, which reads preferred_vendor_id.
        $this->actingAs($this->admin)->putJson(route('admin.inventory-items.update', $item), $this->payload($item, [
            $this->lisanti->id => ['enabled' => false, 'is_preferred' => true],
        ]))->assertOk();

        $this->assertNull($item->fresh()->preferred_vendor_id);
        $this->assertCount(0, $item->fresh()->vendors);
    }

    // ---- Price history mirror ---------------------------------------------

    /** @test */
    public function entering_a_price_records_it_in_the_comparison_history(): void
    {
        $item = $this->item();

        $this->actingAs($this->admin)->putJson(route('admin.inventory-items.update', $item), $this->payload($item, [
            $this->lisanti->id => ['enabled' => true, 'current_price' => 46.50, 'is_preferred' => true],
        ]))->assertOk();

        $price = VendorPrice::where('inventory_item_id', $item->id)->where('vendor_id', $this->lisanti->id)->first();
        $this->assertNotNull($price, 'The price comparison screen reads vendor_prices, so the mapping must write there too.');
        $this->assertEquals(46.50, (float) $price->price);
        $this->assertSame('jug', $price->price_unit);
    }

    /** @test */
    public function resaving_the_same_price_does_not_add_a_duplicate_history_row(): void
    {
        $item = $this->item();
        $mapping = $this->payload($item, [
            $this->lisanti->id => ['enabled' => true, 'current_price' => 46.50, 'is_preferred' => true],
        ]);

        $this->actingAs($this->admin)->putJson(route('admin.inventory-items.update', $item), $mapping)->assertOk();
        $stamp = $this->pivotFor($item, $this->lisanti)->price_updated_at;

        $this->actingAs($this->admin)->putJson(route('admin.inventory-items.update', $item), $mapping)->assertOk();

        $this->assertSame(1, VendorPrice::where('inventory_item_id', $item->id)->count());
        $this->assertSame($stamp, $this->pivotFor($item, $this->lisanti)->price_updated_at);
    }

    /** @test */
    public function changing_a_price_adds_a_history_row_and_moves_the_stamp(): void
    {
        $item = $this->item();

        $this->actingAs($this->admin)->putJson(route('admin.inventory-items.update', $item), $this->payload($item, [
            $this->lisanti->id => ['enabled' => true, 'current_price' => 46.50, 'is_preferred' => true],
        ]))->assertOk();

        $this->actingAs($this->admin)->putJson(route('admin.inventory-items.update', $item), $this->payload($item, [
            $this->lisanti->id => ['enabled' => true, 'current_price' => 49.00, 'is_preferred' => true],
        ]))->assertOk();

        $this->assertSame(2, VendorPrice::where('inventory_item_id', $item->id)->count());
        $this->assertEquals(49.00, (float) $this->pivotFor($item, $this->lisanti)->current_price);
    }

    // ---- Bulk assignment ---------------------------------------------------

    /** @test */
    public function several_items_can_be_assigned_to_one_vendor_at_once(): void
    {
        $steak = $this->item(['name' => 'Steak']);
        $chicken = $this->item(['name' => 'Chicken']);
        // Chicken already has both the target vendor and an unrelated one.
        $chicken->vendors()->attach([$this->lisanti->id, $this->depot->id]);

        $response = $this->actingAs($this->admin)->postJson(route('admin.inventory-items.bulk-assign-vendor'), [
            'store_id' => $this->store->id,
            'vendor_id' => $this->depot->id,
            'item_ids' => [$steak->id, $chicken->id],
        ])->assertOk();

        $this->assertSame(1, $response->json('data.attached'), 'Steak did not have this vendor yet.');
        $this->assertSame(1, $response->json('data.updated'), 'Chicken already had it.');

        $this->assertTrue($steak->fresh()->vendors->contains($this->depot->id));
        // The unrelated vendor the item already had is left in place.
        $this->assertEqualsCanonicalizing(
            [$this->lisanti->id, $this->depot->id],
            $chicken->fresh()->vendors()->pluck('vendors.id')->all()
        );
    }

    /** @test */
    public function bulk_assignment_can_also_set_the_vendor_as_preferred(): void
    {
        $steak = $this->item(['name' => 'Steak']);
        $steak->vendors()->attach($this->lisanti->id, ['is_preferred_vendor' => true]);
        $steak->update(['preferred_vendor_id' => $this->lisanti->id]);

        $this->actingAs($this->admin)->postJson(route('admin.inventory-items.bulk-assign-vendor'), [
            'store_id' => $this->store->id,
            'vendor_id' => $this->depot->id,
            'item_ids' => [$steak->id],
            'make_preferred' => true,
        ])->assertOk();

        $this->assertSame($this->depot->id, $steak->fresh()->preferred_vendor_id);
        $this->assertFalse((bool) $this->pivotFor($steak, $this->lisanti)->is_preferred_vendor);
        $this->assertTrue((bool) $this->pivotFor($steak, $this->depot)->is_preferred_vendor);
        $this->assertSame(1, $steak->fresh()->vendors()->wherePivot('is_preferred_vendor', true)->count());
    }

    /** @test */
    public function bulk_assignment_without_make_preferred_leaves_the_preference_alone(): void
    {
        $steak = $this->item(['name' => 'Steak']);
        $steak->vendors()->attach($this->lisanti->id, ['is_preferred_vendor' => true]);
        $steak->update(['preferred_vendor_id' => $this->lisanti->id]);

        $this->actingAs($this->admin)->postJson(route('admin.inventory-items.bulk-assign-vendor'), [
            'store_id' => $this->store->id,
            'vendor_id' => $this->depot->id,
            'item_ids' => [$steak->id],
        ])->assertOk();

        $this->assertSame($this->lisanti->id, $steak->fresh()->preferred_vendor_id);
    }

    // ---- Store isolation ---------------------------------------------------

    /** @test */
    public function bulk_assignment_skips_items_belonging_to_another_store(): void
    {
        $otherStore = Store::factory()->create(['created_by' => $this->admin->id]);
        $mine = $this->item(['name' => 'Steak']);
        $theirs = InventoryItem::factory()->create([
            'store_id' => $otherStore->id,
            'inventory_category_id' => $this->meats->id,
            'name' => 'Foreign Steak',
        ]);

        $response = $this->actingAs($this->admin)->postJson(route('admin.inventory-items.bulk-assign-vendor'), [
            'store_id' => $this->store->id,
            'vendor_id' => $this->depot->id,
            'item_ids' => [$mine->id, $theirs->id],
        ])->assertOk();

        $this->assertSame(1, $response->json('data.attached'));
        $this->assertStringContainsString('1 item(s) from another store were skipped', $response->json('message'));
        $this->assertCount(0, $theirs->fresh()->vendors);
    }

    /** @test */
    public function bulk_assignment_is_refused_when_no_item_belongs_to_the_store(): void
    {
        $otherStore = Store::factory()->create(['created_by' => $this->admin->id]);
        $theirs = InventoryItem::factory()->create([
            'store_id' => $otherStore->id,
            'inventory_category_id' => $this->meats->id,
        ]);

        $this->actingAs($this->admin)->postJson(route('admin.inventory-items.bulk-assign-vendor'), [
            'store_id' => $this->store->id,
            'vendor_id' => $this->depot->id,
            'item_ids' => [$theirs->id],
        ])->assertStatus(422);

        $this->assertCount(0, $theirs->fresh()->vendors);
    }

    /** @test */
    public function a_manager_cannot_map_vendors_on_another_stores_item(): void
    {
        $otherStore = Store::factory()->create(['created_by' => $this->admin->id]);
        $theirs = InventoryItem::factory()->create([
            'store_id' => $otherStore->id,
            'inventory_category_id' => $this->meats->id,
            'purchase_unit' => 'jug', 'base_unit' => 'lb', 'units_per_purchase' => 35,
        ]);

        $manager = User::factory()->create(['role' => 'manager', 'store_id' => $this->store->id]);
        $manager->assignedStoresPivot()->attach($this->store->id);

        $this->actingAs($manager)->putJson(route('admin.inventory-items.update', $theirs), $this->payload($theirs, [
            $this->lisanti->id => ['enabled' => true, 'is_preferred' => true],
        ]))->assertForbidden();

        $this->assertCount(0, $theirs->fresh()->vendors);
    }

    /** @test */
    public function the_bulk_assign_endpoint_scopes_to_the_users_own_store(): void
    {
        // A manager passing another store's id falls back to their own store,
        // so the foreign item is never in range.
        $otherStore = Store::factory()->create(['created_by' => $this->admin->id]);
        $theirs = InventoryItem::factory()->create([
            'store_id' => $otherStore->id, 'inventory_category_id' => $this->meats->id,
        ]);

        $manager = User::factory()->create(['role' => 'manager', 'store_id' => $this->store->id]);
        $manager->assignedStoresPivot()->attach($this->store->id);

        $this->actingAs($manager)->postJson(route('admin.inventory-items.bulk-assign-vendor'), [
            'store_id' => $otherStore->id,
            'vendor_id' => $this->depot->id,
            'item_ids' => [$theirs->id],
        ])->assertStatus(422);

        $this->assertCount(0, $theirs->fresh()->vendors);
    }

    // ---- Vendor side -------------------------------------------------------

    /** @test */
    public function the_vendor_panel_lists_the_items_it_supplies(): void
    {
        $item = $this->item(['name' => 'Frying Oil']);
        $item->vendors()->attach($this->lisanti->id, [
            'vendor_sku' => 'LIS-OIL-35', 'current_price' => 46.50, 'is_preferred_vendor' => true,
        ]);

        $this->actingAs($this->admin)->getJson("/api/vendors/{$this->lisanti->id}")
            ->assertOk()
            ->assertJsonPath('inventory_items.0.name', 'Frying Oil')
            ->assertJsonPath('inventory_items.0.pivot.vendor_sku', 'LIS-OIL-35')
            ->assertJsonPath('inventory_items.0.pivot.is_preferred_vendor', 1);
    }

    /** @test */
    public function the_vendor_panel_hides_items_from_stores_the_viewer_cannot_see(): void
    {
        $otherStore = Store::factory()->create(['created_by' => $this->admin->id]);
        $theirs = InventoryItem::factory()->create([
            'store_id' => $otherStore->id, 'inventory_category_id' => $this->meats->id,
            'name' => 'Foreign Oil',
        ]);
        $theirs->vendors()->attach($this->lisanti->id);

        $mine = $this->item(['name' => 'My Oil']);
        $mine->vendors()->attach($this->lisanti->id);

        // The vendor API is admin/owner only, so scope an owner to one store.
        $owner = User::factory()->create(['role' => 'owner', 'state' => 'PA']);
        $owner->ownedStores()->attach($this->store->id);

        $response = $this->actingAs($owner)->getJson("/api/vendors/{$this->lisanti->id}")->assertOk();

        $names = collect($response->json('inventory_items'))->pluck('name')->all();
        $this->assertContains('My Oil', $names);
        $this->assertNotContains('Foreign Oil', $names);
    }

    // ---- The "no vendor" warning -------------------------------------------

    /** @test */
    public function an_item_with_no_vendor_saves_and_is_flagged_rather_than_blocked(): void
    {
        $item = $this->item(['name' => 'Unmapped Item']);

        // Saving with an empty mapping must succeed: the client sets items up
        // before pricing them.
        $this->actingAs($this->admin)
            ->putJson(route('admin.inventory-items.update', $item), $this->payload($item, []))
            ->assertOk();

        $this->assertCount(0, $item->fresh()->vendors);

        $this->actingAs($this->admin)
            ->get(route('admin.inventory-items.index', ['store_id' => $this->store->id]))
            ->assertOk()
            ->assertSee('no vendor yet')
            ->assertSee('no vendor');
    }

    /** @test */
    public function the_unmapped_filter_shows_only_items_without_a_vendor(): void
    {
        $mapped = $this->item(['name' => 'Alpha Mapped Fixture']);
        $mapped->vendors()->attach($this->lisanti->id);
        $this->item(['name' => 'Beta Unmapped Fixture']);

        $this->actingAs($this->admin)
            ->get(route('admin.inventory-items.index', ['store_id' => $this->store->id, 'unmapped' => 1]))
            ->assertOk()
            ->assertSee('Beta Unmapped Fixture')
            ->assertDontSee('Alpha Mapped Fixture');
    }

    // ---- Seeded mappings ---------------------------------------------------

    /** @test */
    public function the_seeder_maps_every_sample_item_with_skus_prices_and_one_preferred(): void
    {
        $this->seed(\Database\Seeders\VendorsSeeder::class);
        $this->seed(\Database\Seeders\InventoryItemsSeeder::class);

        $oil = InventoryItem::where('store_id', $this->store->id)->where('name', 'Frying Oil')->firstOrFail();

        $this->assertCount(3, $oil->vendors);
        $this->assertSame(1, $oil->vendors()->wherePivot('is_preferred_vendor', true)->count());
        $this->assertSame("Sam's Club", $oil->preferredVendor->vendor_name);

        $sams = $oil->vendors()->where('vendor_name', "Sam's Club")->first();
        $this->assertSame('SC-OIL-35', $sams->pivot->vendor_sku);
        $this->assertEquals(42.00, (float) $sams->pivot->current_price);

        // Every seeded item agrees between the column and the pivot flag.
        foreach (InventoryItem::where('store_id', $this->store->id)->with('vendors')->get() as $item) {
            $flagged = $item->vendors->firstWhere('pivot.is_preferred_vendor', true);
            $this->assertSame(
                $item->preferred_vendor_id,
                $flagged?->id,
                "preferred_vendor_id disagrees with the pivot flag on {$item->name}."
            );
        }
    }
}
