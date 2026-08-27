<?php

namespace Tests\Feature;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryCategoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Migration 2026_08_25_000002 inserts the eight canonical categories, so a
     * fresh database is never empty. Clear them here and let each test own its
     * fixture; the_migration_seeds_the_eight_order_guide_categories covers the
     * seeded set itself.
     */
    protected function setUp(): void
    {
        parent::setUp();

        InventoryCategory::query()->delete();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function store(User $admin): Store
    {
        return Store::factory()->create(['created_by' => $admin->id]);
    }

    /** @test */
    public function the_index_lists_categories_in_display_order(): void
    {
        $admin = $this->admin();
        InventoryCategory::factory()->create(['name' => 'Beverages', 'display_order' => 80]);
        InventoryCategory::factory()->create(['name' => 'Meats', 'display_order' => 10]);

        $response = $this->actingAs($admin)->get(route('admin.inventory-categories.index'))->assertOk();

        $body = $response->getContent();
        $this->assertLessThan(
            strpos($body, 'Beverages'),
            strpos($body, 'Meats'),
            'Meats (display_order 10) should render before Beverages (display_order 80).'
        );
    }

    /** @test */
    public function the_index_shows_active_and_inactive_item_counts(): void
    {
        $admin = $this->admin();
        $store = $this->store($admin);
        $category = InventoryCategory::factory()->create(['name' => 'Meats']);
        InventoryItem::factory()->count(2)->create([
            'store_id' => $store->id, 'inventory_category_id' => $category->id, 'is_active' => true,
        ]);
        InventoryItem::factory()->create([
            'store_id' => $store->id, 'inventory_category_id' => $category->id, 'is_active' => false,
        ]);

        $this->actingAs($admin)->get(route('admin.inventory-categories.index'))
            ->assertOk()
            ->assertSee('2 active')
            ->assertSee('1 inactive');
    }

    /** @test */
    public function an_admin_can_add_a_category(): void
    {
        $this->actingAs($this->admin())
            ->postJson(route('admin.inventory-categories.store'), ['name' => 'Frozen Goods'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Frozen Goods');

        $this->assertDatabaseHas('inventory_categories', ['name' => 'Frozen Goods']);
    }

    /** @test */
    public function a_new_category_lands_at_the_end_of_the_list(): void
    {
        InventoryCategory::factory()->create(['name' => 'Meats', 'display_order' => 10]);
        InventoryCategory::factory()->create(['name' => 'Beverages', 'display_order' => 80]);

        $this->actingAs($this->admin())
            ->postJson(route('admin.inventory-categories.store'), ['name' => 'Frozen Goods'])
            ->assertCreated()
            ->assertJsonPath('data.display_order', 90);
    }

    /** @test */
    public function category_names_must_be_unique(): void
    {
        InventoryCategory::factory()->create(['name' => 'Meats']);

        $this->actingAs($this->admin())
            ->postJson(route('admin.inventory-categories.store'), ['name' => 'Meats'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');

        $this->assertSame(1, InventoryCategory::where('name', 'Meats')->count());
    }

    /** @test */
    public function an_admin_can_rename_a_category(): void
    {
        $category = InventoryCategory::factory()->create(['name' => 'Veggies']);

        $this->actingAs($this->admin())
            ->putJson(route('admin.inventory-categories.update', $category), ['name' => 'Produce'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Produce');

        $this->assertSame('Produce', $category->fresh()->name);
    }

    /** @test */
    public function renaming_keeps_the_categorys_own_name_valid(): void
    {
        $category = InventoryCategory::factory()->create(['name' => 'Veggies', 'display_order' => 50]);

        $this->actingAs($this->admin())
            ->putJson(route('admin.inventory-categories.update', $category), ['name' => 'Veggies'])
            ->assertOk();

        // display_order was not sent, so it must survive the update untouched.
        $this->assertSame(50, $category->fresh()->display_order);
    }

    /** @test */
    public function renaming_cannot_collide_with_another_category(): void
    {
        InventoryCategory::factory()->create(['name' => 'Meats']);
        $category = InventoryCategory::factory()->create(['name' => 'Veggies']);

        $this->actingAs($this->admin())
            ->putJson(route('admin.inventory-categories.update', $category), ['name' => 'Meats'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');

        $this->assertSame('Veggies', $category->fresh()->name);
    }

    /** @test */
    public function an_admin_can_delete_an_empty_category(): void
    {
        $category = InventoryCategory::factory()->create(['name' => 'Unused']);

        $this->actingAs($this->admin())
            ->deleteJson(route('admin.inventory-categories.destroy', $category))
            ->assertOk();

        $this->assertDatabaseMissing('inventory_categories', ['id' => $category->id]);
    }

    /** @test */
    public function deleting_is_refused_while_active_items_are_filed_under_it(): void
    {
        $admin = $this->admin();
        $store = $this->store($admin);
        $category = InventoryCategory::factory()->create(['name' => 'Meats']);
        InventoryItem::factory()->create([
            'store_id' => $store->id, 'inventory_category_id' => $category->id,
            'name' => 'Ribeye Steak', 'is_active' => true,
        ]);
        InventoryItem::factory()->create([
            'store_id' => $store->id, 'inventory_category_id' => $category->id,
            'name' => 'Chicken Breast', 'is_active' => true,
        ]);

        $response = $this->actingAs($admin)
            ->deleteJson(route('admin.inventory-categories.destroy', $category));

        $response->assertStatus(422)->assertJsonCount(2, 'items');
        $this->assertStringContainsString('2 active items', $response->json('error'));
        $this->assertStringContainsString('Meats', $response->json('error'));
        $this->assertDatabaseHas('inventory_categories', ['id' => $category->id]);
    }

    /** @test */
    public function deleting_a_category_holding_only_inactive_items_succeeds_and_says_so(): void
    {
        $admin = $this->admin();
        $store = $this->store($admin);
        $category = InventoryCategory::factory()->create(['name' => 'Retired']);
        $item = InventoryItem::factory()->create([
            'store_id' => $store->id, 'inventory_category_id' => $category->id, 'is_active' => false,
        ]);

        $response = $this->actingAs($admin)
            ->deleteJson(route('admin.inventory-categories.destroy', $category))
            ->assertOk();

        $this->assertStringContainsString('1 inactive item', $response->json('message'));
        $this->assertDatabaseMissing('inventory_categories', ['id' => $category->id]);

        // The FK is nullOnDelete, so the item survives without a category.
        $this->assertDatabaseHas('inventory_items', ['id' => $item->id, 'inventory_category_id' => null]);
    }

    /** @test */
    public function an_admin_can_reorder_categories_by_drag_and_drop(): void
    {
        $meats = InventoryCategory::factory()->create(['name' => 'Meats', 'display_order' => 10]);
        $breads = InventoryCategory::factory()->create(['name' => 'Breads', 'display_order' => 20]);
        $cheese = InventoryCategory::factory()->create(['name' => 'Cheese', 'display_order' => 30]);

        $this->actingAs($this->admin())
            ->postJson(route('admin.inventory-categories.reorder'), [
                'order' => [$cheese->id, $meats->id, $breads->id],
            ])
            ->assertOk();

        $this->assertSame(10, $cheese->fresh()->display_order);
        $this->assertSame(20, $meats->fresh()->display_order);
        $this->assertSame(30, $breads->fresh()->display_order);

        $this->assertSame(
            ['Cheese', 'Meats', 'Breads'],
            InventoryCategory::ordered()->pluck('name')->all()
        );
    }

    /** @test */
    public function reordering_rejects_an_unknown_category_id(): void
    {
        $meats = InventoryCategory::factory()->create(['name' => 'Meats', 'display_order' => 10]);

        $this->actingAs($this->admin())
            ->postJson(route('admin.inventory-categories.reorder'), ['order' => [$meats->id, 999999]])
            ->assertStatus(422)
            ->assertJsonValidationErrors('order.1');

        $this->assertSame(10, $meats->fresh()->display_order);
    }

    /** @test */
    public function non_admins_cannot_reach_category_management(): void
    {
        $owner = User::factory()->create(['role' => 'owner', 'state' => 'PA']);
        $manager = User::factory()->create(['role' => 'manager']);
        $category = InventoryCategory::factory()->create(['name' => 'Meats']);

        foreach ([$owner, $manager] as $user) {
            $this->actingAs($user)->get(route('admin.inventory-categories.index'))->assertForbidden();
            $this->actingAs($user)->postJson(route('admin.inventory-categories.store'), ['name' => 'Sneaky'])->assertForbidden();
            $this->actingAs($user)->putJson(route('admin.inventory-categories.update', $category), ['name' => 'Renamed'])->assertForbidden();
            $this->actingAs($user)->deleteJson(route('admin.inventory-categories.destroy', $category))->assertForbidden();
            $this->actingAs($user)->postJson(route('admin.inventory-categories.reorder'), ['order' => [$category->id]])->assertForbidden();
        }

        $this->assertSame('Meats', $category->fresh()->name);
        $this->assertDatabaseMissing('inventory_categories', ['name' => 'Sneaky']);
    }

    /** @test */
    public function the_migration_seeds_the_eight_order_guide_categories(): void
    {
        // setUp clears them, so re-run the seeder to assert on what ships.
        $this->seed(\Database\Seeders\InventoryCategoriesSeeder::class);

        $this->assertSame([
            'Meats', 'Breads', 'Cheese', 'Sides', 'Veggies', 'Canned Goods & Misc', 'Paper Goods', 'Beverages',
        ], InventoryCategory::ordered()->pluck('name')->all());
    }

    /** @test */
    public function guests_are_redirected_to_login(): void
    {
        $this->get(route('admin.inventory-categories.index'))->assertRedirect(route('login'));
    }
}
