<?php

namespace Tests\Feature;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Store;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class InventoryItemMasterTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Store $store;

    private InventoryCategory $meats;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->store = Store::factory()->create(['created_by' => $this->admin->id]);
        // Seeded by migration 2026_08_25_000002.
        $this->meats = InventoryCategory::where('name', 'Meats')->firstOrFail();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'store_id' => $this->store->id,
            'name' => 'Ribeye Steak',
            'inventory_category_id' => $this->meats->id,
            'purchase_unit' => 'box',
            'base_unit' => 'portion',
            'units_per_purchase' => 53,
            'portion_size' => 3,
            'portion_unit' => 'oz',
        ], $overrides);
    }

    private function csv(string $contents): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('order-guide.csv', $contents);
    }

    // ---- CRUD --------------------------------------------------------------

    /** @test */
    public function the_index_renders_with_items(): void
    {
        InventoryItem::factory()->create([
            'store_id' => $this->store->id,
            'inventory_category_id' => $this->meats->id,
            'name' => 'Ribeye Steak',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.inventory-items.index', ['store_id' => $this->store->id]))
            ->assertOk()
            ->assertSee('Ribeye Steak')
            ->assertSee('Portions / Unit');
    }

    /** @test */
    public function an_admin_can_create_an_item(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('admin.inventory-items.store'), $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.name', 'Ribeye Steak');

        $this->assertDatabaseHas('inventory_items', [
            'store_id' => $this->store->id,
            'name' => 'Ribeye Steak',
            'inventory_category_id' => $this->meats->id,
            'purchase_unit' => 'box',
            'base_unit' => 'portion',
            'portion_unit' => 'oz',
        ]);

        $item = InventoryItem::where('name', 'Ribeye Steak')->firstOrFail();
        $this->assertEquals(53, (float) $item->units_per_purchase);
        $this->assertEquals(3, (float) $item->portion_size);
        // The legacy free-text column stays in step for the older screens.
        $this->assertSame('Meats', $item->category);
    }

    /** @test */
    public function creating_an_item_records_every_vendor_that_supplies_it(): void
    {
        $lisanti = Vendor::factory()->create(['vendor_name' => 'Lisanti']);
        $depot = Vendor::factory()->create(['vendor_name' => 'Restaurant Depot']);

        $this->actingAs($this->admin)->postJson(route('admin.inventory-items.store'), $this->payload([
            'vendors' => [
                $lisanti->id => ['enabled' => true, 'is_preferred' => true],
                $depot->id => ['enabled' => true, 'is_preferred' => false],
            ],
        ]))->assertCreated();

        $item = InventoryItem::where('name', 'Ribeye Steak')->firstOrFail();

        $this->assertEqualsCanonicalizing(
            [$lisanti->id, $depot->id],
            $item->vendors()->pluck('vendors.id')->all()
        );
        // The preferred vendor is mirrored onto the pivot, so both readings agree.
        $this->assertSame($lisanti->id, $item->preferred_vendor_id);
        $this->assertTrue((bool) $item->vendors()->where('vendors.id', $lisanti->id)->first()->pivot->is_preferred_vendor);
        $this->assertFalse((bool) $item->vendors()->where('vendors.id', $depot->id)->first()->pivot->is_preferred_vendor);
    }

    /** @test */
    public function an_admin_can_update_an_item(): void
    {
        $item = InventoryItem::factory()->create([
            'store_id' => $this->store->id,
            'inventory_category_id' => $this->meats->id,
            'name' => 'Steak',
        ]);

        $this->actingAs($this->admin)
            ->putJson(route('admin.inventory-items.update', $item), $this->payload([
                'name' => 'Ribeye Steak',
                'units_per_purchase' => 48,
            ]))
            ->assertOk();

        $item->refresh();
        $this->assertSame('Ribeye Steak', $item->name);
        $this->assertEquals(48, (float) $item->units_per_purchase);
    }

    /**
     * The client's non-negotiable: suppliers change pack sizes, so this value
     * must stay editable for the life of the item.
     *
     * @test
     */
    public function portions_per_unit_can_be_changed_at_any_time(): void
    {
        $item = InventoryItem::factory()->create([
            'store_id' => $this->store->id,
            'inventory_category_id' => $this->meats->id,
            'name' => 'Steak',
            'units_per_purchase' => 53,
        ]);

        foreach ([48, 60, 53.5] as $newPackSize) {
            $this->actingAs($this->admin)
                ->putJson(route('admin.inventory-items.update', $item), $this->payload([
                    'name' => 'Steak',
                    'units_per_purchase' => $newPackSize,
                ]))
                ->assertOk();

            $this->assertEquals($newPackSize, (float) $item->fresh()->units_per_purchase);
        }
    }

    /** @test */
    public function portions_per_unit_must_be_greater_than_zero(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('admin.inventory-items.store'), $this->payload(['units_per_purchase' => 0]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('units_per_purchase');

        $this->assertDatabaseMissing('inventory_items', ['name' => 'Ribeye Steak']);
    }

    /** @test */
    public function a_portion_size_without_a_unit_is_rejected(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('admin.inventory-items.store'), $this->payload([
                'portion_size' => 3, 'portion_unit' => null,
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('portion_unit');
    }

    /** @test */
    public function deleting_an_item_hides_it_without_losing_history(): void
    {
        $item = InventoryItem::factory()->create([
            'store_id' => $this->store->id,
            'inventory_category_id' => $this->meats->id,
        ]);

        $this->actingAs($this->admin)
            ->deleteJson(route('admin.inventory-items.destroy', $item))
            ->assertOk();

        $this->assertSoftDeleted('inventory_items', ['id' => $item->id]);
        $this->assertNotNull(InventoryItem::withTrashed()->find($item->id));
    }

    // ---- Filters -----------------------------------------------------------

    /** @test */
    public function the_list_can_be_filtered_by_category_vendor_status_and_search(): void
    {
        $breads = InventoryCategory::where('name', 'Breads')->firstOrFail();
        $lisanti = Vendor::factory()->create(['vendor_name' => 'Lisanti']);

        // Names deliberately unlike anything in the page chrome, so assertDontSee
        // cannot trip over a placeholder or help text.
        $meatItem = InventoryItem::factory()->create([
            'store_id' => $this->store->id, 'inventory_category_id' => $this->meats->id,
            'name' => 'Zeta Meat Fixture', 'is_active' => true,
        ]);
        $meatItem->vendors()->attach($lisanti->id);

        InventoryItem::factory()->create([
            'store_id' => $this->store->id, 'inventory_category_id' => $breads->id,
            'name' => 'Omega Bread Fixture', 'is_active' => false,
        ]);

        $url = fn (array $params) => route('admin.inventory-items.index', $params + ['store_id' => $this->store->id]);

        $this->actingAs($this->admin)->get($url(['inventory_category_id' => $this->meats->id]))
            ->assertSee('Zeta Meat Fixture')->assertDontSee('Omega Bread Fixture');

        $this->actingAs($this->admin)->get($url(['vendor_id' => $lisanti->id]))
            ->assertSee('Zeta Meat Fixture')->assertDontSee('Omega Bread Fixture');

        $this->actingAs($this->admin)->get($url(['is_active' => '0']))
            ->assertSee('Omega Bread Fixture')->assertDontSee('Zeta Meat Fixture');

        $this->actingAs($this->admin)->get($url(['search' => 'Omega']))
            ->assertSee('Omega Bread Fixture')->assertDontSee('Zeta Meat Fixture');
    }

    /** @test */
    public function items_from_another_store_are_not_listed_or_editable(): void
    {
        $otherStore = Store::factory()->create(['created_by' => $this->admin->id]);
        $foreign = InventoryItem::factory()->create([
            'store_id' => $otherStore->id, 'inventory_category_id' => $this->meats->id,
            'name' => 'Someone Elses Steak',
        ]);

        $manager = User::factory()->create(['role' => 'manager', 'store_id' => $this->store->id]);
        $manager->assignedStoresPivot()->attach($this->store->id);

        $this->actingAs($manager)
            ->get(route('admin.inventory-items.index'))
            ->assertOk()
            ->assertDontSee('Someone Elses Steak');

        $this->actingAs($manager)
            ->putJson(route('admin.inventory-items.update', $foreign), $this->payload(['name' => 'Hijacked']))
            ->assertForbidden();

        $this->assertSame('Someone Elses Steak', $foreign->fresh()->name);
    }

    // ---- CSV import --------------------------------------------------------

    /** @test */
    public function the_import_preview_classifies_new_duplicate_and_broken_rows(): void
    {
        InventoryItem::factory()->create([
            'store_id' => $this->store->id, 'inventory_category_id' => $this->meats->id,
            'name' => 'Steak', 'units_per_purchase' => 53,
        ]);

        $csv = <<<CSV
        Name,Category,Unit,Portions per Unit,Portion Size,Portion Unit
        Steak,Meats,box,48,3,oz
        Pita,Breads,case,120,,
        Broken Item,Meats,box,not-a-number,,
        Mystery Item,Frozen Goods,bag,10,,
        CSV;

        $response = $this->actingAs($this->admin)
            ->post(route('admin.inventory-items.import.preview'), [
                'store_id' => $this->store->id,
                'file' => $this->csv($csv),
            ]);

        $response->assertOk()
            ->assertSee('Bulk Import Preview')
            ->assertSee('Portions per unit is missing or not a number.')
            ->assertSee('Frozen Goods')
            ->assertSee('Update existing');

        // Nothing is written during preview.
        $this->assertDatabaseMissing('inventory_items', ['name' => 'Pita']);
        $this->assertEquals(53, (float) InventoryItem::where('name', 'Steak')->first()->units_per_purchase);
    }

    /** @test */
    public function the_import_commits_creates_updates_and_skips(): void
    {
        $existing = InventoryItem::factory()->create([
            'store_id' => $this->store->id, 'inventory_category_id' => $this->meats->id,
            'name' => 'Steak', 'units_per_purchase' => 53,
        ]);
        $breads = InventoryCategory::where('name', 'Breads')->firstOrFail();

        $response = $this->actingAs($this->admin)->post(route('admin.inventory-items.import.commit'), [
            'store_id' => $this->store->id,
            'rows' => [
                [
                    'name' => 'Steak', 'category' => 'Meats', 'unit' => 'box',
                    'portions_per_unit' => 48, 'portion_size' => 3, 'portion_unit' => 'oz',
                    'inventory_category_id' => $this->meats->id,
                    'existing_item_id' => $existing->id, 'action' => 'update',
                ],
                [
                    'name' => 'Pita', 'category' => 'Breads', 'unit' => 'case',
                    'portions_per_unit' => 120, 'portion_size' => null, 'portion_unit' => '',
                    'inventory_category_id' => $breads->id,
                    'existing_item_id' => null, 'action' => 'create',
                ],
                [
                    'name' => 'Ignored', 'category' => 'Meats', 'unit' => 'box',
                    'portions_per_unit' => 10, 'portion_size' => null, 'portion_unit' => '',
                    'inventory_category_id' => $this->meats->id,
                    'existing_item_id' => null, 'action' => 'skip',
                ],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Import finished: 1 created, 1 updated, 1 skipped.');

        $this->assertEquals(48, (float) $existing->fresh()->units_per_purchase);
        $this->assertDatabaseHas('inventory_items', [
            'store_id' => $this->store->id, 'name' => 'Pita', 'units_per_purchase' => 120,
        ]);
        $this->assertDatabaseMissing('inventory_items', ['name' => 'Ignored']);
    }

    /** @test */
    public function the_import_can_create_a_category_that_does_not_exist_yet(): void
    {
        $this->assertDatabaseMissing('inventory_categories', ['name' => 'Frozen Goods']);

        $this->actingAs($this->admin)->post(route('admin.inventory-items.import.commit'), [
            'store_id' => $this->store->id,
            'create_missing_categories' => true,
            'rows' => [[
                'name' => 'Tator Tots', 'category' => 'Frozen Goods', 'unit' => 'case',
                'portions_per_unit' => 6, 'portion_size' => null, 'portion_unit' => '',
                'inventory_category_id' => null, 'existing_item_id' => null, 'action' => 'create',
            ]],
        ])->assertRedirect();

        $category = InventoryCategory::where('name', 'Frozen Goods')->first();
        $this->assertNotNull($category);
        $this->assertDatabaseHas('inventory_items', [
            'name' => 'Tator Tots', 'inventory_category_id' => $category->id,
        ]);
    }

    /** @test */
    public function the_import_skips_an_unknown_category_when_creation_is_declined(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.inventory-items.import.commit'), [
            'store_id' => $this->store->id,
            'create_missing_categories' => false,
            'rows' => [[
                'name' => 'Tator Tots', 'category' => 'Frozen Goods', 'unit' => 'case',
                'portions_per_unit' => 6, 'portion_size' => null, 'portion_unit' => '',
                'inventory_category_id' => null, 'existing_item_id' => null, 'action' => 'create',
            ]],
        ]);

        $response->assertSessionHas('success', 'Import finished: 0 created, 0 updated, 1 skipped.');
        $this->assertDatabaseMissing('inventory_categories', ['name' => 'Frozen Goods']);
        $this->assertDatabaseMissing('inventory_items', ['name' => 'Tator Tots']);
    }

    /** @test */
    public function a_hand_edited_bad_pack_size_is_still_rejected_at_commit(): void
    {
        // The preview marks this row broken; re-posting it with the error field
        // stripped must not sneak it past.
        $response = $this->actingAs($this->admin)->post(route('admin.inventory-items.import.commit'), [
            'store_id' => $this->store->id,
            'rows' => [[
                'name' => 'Sneaky Item', 'category' => 'Meats', 'unit' => 'box',
                'portions_per_unit' => 0, 'portion_size' => null, 'portion_unit' => '',
                'inventory_category_id' => $this->meats->id,
                'existing_item_id' => null, 'action' => 'create',
            ]],
        ]);

        $response->assertSessionHas('success', 'Import finished: 0 created, 0 updated, 1 skipped.');
        $this->assertDatabaseMissing('inventory_items', ['name' => 'Sneaky Item']);
    }

    /** @test */
    public function the_importer_understands_the_clients_alternative_header_names(): void
    {
        $csv = <<<CSV
        Item Name,Group,Pack,Portions per Box,Serving Size,Size Unit
        Steak,Meats,box,"1,053",3,oz
        CSV;

        $this->actingAs($this->admin)
            ->post(route('admin.inventory-items.import.preview'), [
                'store_id' => $this->store->id,
                'file' => $this->csv($csv),
            ])
            ->assertOk()
            ->assertSee('Steak')
            ->assertSee('1053'); // thousands separator tolerated
    }

    /** @test */
    public function an_import_file_without_a_name_column_is_rejected(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.inventory-items.import.preview'), [
                'store_id' => $this->store->id,
                'file' => $this->csv("Category,Unit\nMeats,box"),
            ])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    /** @test */
    public function an_import_row_cannot_update_an_item_belonging_to_another_store(): void
    {
        $otherStore = Store::factory()->create(['created_by' => $this->admin->id]);
        $foreign = InventoryItem::factory()->create([
            'store_id' => $otherStore->id, 'inventory_category_id' => $this->meats->id,
            'name' => 'Foreign Steak', 'units_per_purchase' => 53,
        ]);

        $this->actingAs($this->admin)->post(route('admin.inventory-items.import.commit'), [
            'store_id' => $this->store->id,
            'rows' => [[
                'name' => 'Foreign Steak', 'category' => 'Meats', 'unit' => 'box',
                'portions_per_unit' => 999, 'portion_size' => null, 'portion_unit' => '',
                'inventory_category_id' => $this->meats->id,
                'existing_item_id' => $foreign->id, 'action' => 'update',
            ]],
        ])->assertSessionHas('success', 'Import finished: 0 created, 0 updated, 1 skipped.');

        $this->assertEquals(53, (float) $foreign->fresh()->units_per_purchase);
    }

    // ---- Seeder ------------------------------------------------------------

    /** @test */
    public function the_sample_seeder_loads_the_clients_ten_items(): void
    {
        $this->seed(\Database\Seeders\VendorsSeeder::class);
        $this->seed(\Database\Seeders\InventoryItemsSeeder::class);

        $items = InventoryItem::where('store_id', $this->store->id)->get()->keyBy('name');
        $this->assertCount(10, $items);

        $steak = $items->get('Steak');
        $this->assertEquals(53, (float) $steak->units_per_purchase);
        $this->assertEquals(3, (float) $steak->portion_size);
        $this->assertSame('oz', $steak->portion_unit);
        $this->assertEqualsCanonicalizing(
            ['Lisanti', 'Restaurant Depot'],
            $steak->vendors()->pluck('vendor_name')->all()
        );

        // Frying oil is the three-vendor case the brief calls out.
        $this->assertEqualsCanonicalizing(
            ["Sam's Club", 'Lisanti', 'Restaurant Depot'],
            $items->get('Frying Oil')->vendors()->pluck('vendor_name')->all()
        );

        $this->assertEquals(60, (float) $items->get('8" Bread')->units_per_purchase);
        $this->assertEquals(120, (float) $items->get('Pita')->units_per_purchase);
    }

    /** @test */
    public function re_running_the_sample_seeder_does_not_duplicate_items(): void
    {
        $this->seed(\Database\Seeders\VendorsSeeder::class);
        $this->seed(\Database\Seeders\InventoryItemsSeeder::class);
        $this->seed(\Database\Seeders\InventoryItemsSeeder::class);

        $this->assertSame(10, InventoryItem::where('store_id', $this->store->id)->count());
        $this->assertSame(2, InventoryItem::where('name', 'Steak')->firstOrFail()->vendors()->count());
    }
}
