<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\Store;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function owner(): User
    {
        return User::factory()->create(['role' => 'owner', 'state' => 'PA']);
    }

    private function manager(): User
    {
        return User::factory()->create(['role' => 'manager']);
    }

    /** @test */
    public function the_index_page_lists_vendors_with_contact_phone_and_item_count(): void
    {
        $admin = $this->admin();
        $store = Store::factory()->create(['created_by' => $admin->id]);
        $vendor = Vendor::factory()->create([
            'vendor_name' => 'Lisanti Foods',
            'contact_name' => 'Marco Rossi',
            'contact_phone' => '215-555-0142',
        ]);
        $item = InventoryItem::factory()->create(['store_id' => $store->id, 'name' => 'Ribeye Steak']);
        $vendor->inventoryItems()->attach($item->id);

        $this->actingAs($admin)->get(route('admin.vendors.index'))
            ->assertOk()
            ->assertSee('Lisanti Foods')
            ->assertSee('Marco Rossi')
            ->assertSee('215-555-0142')
            ->assertSee('# Items');
    }

    /** @test */
    public function search_filters_by_name_and_contact_details(): void
    {
        $admin = $this->admin();
        Vendor::factory()->create(['vendor_name' => 'Restaurant Depot', 'contact_name' => 'Dana Reed']);
        Vendor::factory()->create(['vendor_name' => 'Coca-Cola', 'contact_name' => 'Sam Vale']);

        // Searching a contact name used to throw: the query referenced a
        // `vendors.email` column that does not exist on this table.
        $this->actingAs($admin)->get(route('admin.vendors.index', ['search' => 'Dana']))
            ->assertOk()
            ->assertSee('Restaurant Depot')
            ->assertDontSee('Coca-Cola');
    }

    /** @test */
    public function an_admin_can_create_a_vendor(): void
    {
        $response = $this->actingAs($this->admin())->postJson('/api/vendors', [
            'vendor_name' => 'Sam\'s Club',
            'vendor_type' => 'Food',
            'contact_name' => 'Priya Shah',
            'contact_email' => 'priya@samsclub.test',
            'contact_phone' => '210-555-0100',
            'website' => 'https://www.samsclub.com',
        ]);

        $response->assertCreated()->assertJsonPath('data.vendor_name', "Sam's Club");
        $this->assertDatabaseHas('vendors', [
            'vendor_name' => "Sam's Club",
            'website' => 'https://www.samsclub.com',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function creating_a_vendor_rejects_a_malformed_website(): void
    {
        $this->actingAs($this->admin())->postJson('/api/vendors', [
            'vendor_name' => 'Bad Website Vendor',
            'vendor_type' => 'Food',
            'website' => 'samsclub',
        ])->assertStatus(422)->assertJsonValidationErrors('website');

        $this->assertDatabaseMissing('vendors', ['vendor_name' => 'Bad Website Vendor']);
    }

    /** @test */
    public function an_admin_can_update_a_vendor(): void
    {
        $vendor = Vendor::factory()->create(['vendor_name' => 'HEB', 'contact_phone' => '000']);

        $this->actingAs($this->admin())->putJson("/api/vendors/{$vendor->id}", [
            'vendor_name' => 'HEB Grocery',
            'vendor_type' => 'Supplies',
            'contact_phone' => '512-555-0199',
            'website' => 'https://www.heb.com',
        ])->assertOk();

        $this->assertDatabaseHas('vendors', [
            'id' => $vendor->id,
            'vendor_name' => 'HEB Grocery',
            'vendor_type' => 'Supplies',
            'contact_phone' => '512-555-0199',
            'website' => 'https://www.heb.com',
        ]);
    }

    /** @test */
    public function updating_a_vendor_cannot_steal_another_vendors_identifier(): void
    {
        $taken = Vendor::factory()->create(['vendor_identifier' => 'LISANTI']);
        $vendor = Vendor::factory()->create(['vendor_identifier' => 'HEB']);

        $this->actingAs($this->admin())->putJson("/api/vendors/{$vendor->id}", [
            'vendor_name' => $vendor->vendor_name,
            'vendor_type' => 'Food',
            'vendor_identifier' => 'LISANTI',
        ])->assertStatus(422)->assertJsonValidationErrors('vendor_identifier');

        $this->assertSame('HEB', $vendor->fresh()->vendor_identifier);
        $this->assertSame('LISANTI', $taken->fresh()->vendor_identifier);
    }

    /** @test */
    public function updating_a_vendor_keeps_its_own_identifier(): void
    {
        $vendor = Vendor::factory()->create(['vendor_identifier' => 'HEB']);

        $this->actingAs($this->admin())->putJson("/api/vendors/{$vendor->id}", [
            'vendor_name' => 'HEB Grocery',
            'vendor_type' => 'Food',
            'vendor_identifier' => 'HEB',
        ])->assertOk();

        $this->assertSame('HEB Grocery', $vendor->fresh()->vendor_name);
    }

    /** @test */
    public function an_admin_can_soft_delete_a_vendor_with_no_items(): void
    {
        $vendor = Vendor::factory()->create();

        $this->actingAs($this->admin())->deleteJson("/api/vendors/{$vendor->id}")->assertOk();

        // Hidden, not gone: the row survives so expense history keeps its vendor.
        $this->assertSoftDeleted('vendors', ['id' => $vendor->id]);
        $this->assertNull(Vendor::find($vendor->id));
        $this->assertNotNull(Vendor::withTrashed()->find($vendor->id));
    }

    /** @test */
    public function deleting_is_refused_while_inventory_items_are_assigned(): void
    {
        $admin = $this->admin();
        $store = Store::factory()->create(['created_by' => $admin->id]);
        $vendor = Vendor::factory()->create();
        $steak = InventoryItem::factory()->create(['store_id' => $store->id, 'name' => 'Ribeye Steak']);
        $oil = InventoryItem::factory()->create(['store_id' => $store->id, 'name' => 'Frying Oil']);
        $vendor->inventoryItems()->attach([$steak->id, $oil->id]);

        $response = $this->actingAs($admin)->deleteJson("/api/vendors/{$vendor->id}");

        $response->assertStatus(422)
            ->assertJsonPath('items.0.name', 'Ribeye Steak')
            ->assertJsonPath('items.1.name', 'Frying Oil');
        $this->assertStringContainsString('2 inventory items', $response->json('error'));
        $this->assertNotSoftDeleted('vendors', ['id' => $vendor->id]);
    }

    /** @test */
    public function deleting_is_refused_while_the_vendor_is_an_items_preferred_vendor(): void
    {
        $admin = $this->admin();
        $store = Store::factory()->create(['created_by' => $admin->id]);
        $vendor = Vendor::factory()->create();
        InventoryItem::factory()->create([
            'store_id' => $store->id,
            'name' => '8 inch Bread',
            'preferred_vendor_id' => $vendor->id,
        ]);

        $this->actingAs($admin)->deleteJson("/api/vendors/{$vendor->id}")
            ->assertStatus(422)
            ->assertJsonPath('items.0.name', '8 inch Bread');
        $this->assertNotSoftDeleted('vendors', ['id' => $vendor->id]);
    }

    /** @test */
    public function an_item_assigned_to_both_the_pivot_and_preferred_vendor_is_listed_once(): void
    {
        $admin = $this->admin();
        $store = Store::factory()->create(['created_by' => $admin->id]);
        $vendor = Vendor::factory()->create();
        $item = InventoryItem::factory()->create([
            'store_id' => $store->id,
            'name' => 'Frying Oil',
            'preferred_vendor_id' => $vendor->id,
        ]);
        $vendor->inventoryItems()->attach($item->id);

        $response = $this->actingAs($admin)->deleteJson("/api/vendors/{$vendor->id}");

        $response->assertStatus(422)->assertJsonCount(1, 'items');
        $this->assertStringContainsString('1 inventory item.', $response->json('error'));
    }

    /** @test */
    public function an_admin_can_restore_a_hidden_vendor(): void
    {
        $vendor = Vendor::factory()->create();
        $vendor->delete();

        $this->actingAs($this->admin())->postJson("/api/vendors/{$vendor->id}/restore")->assertOk();

        $this->assertNotSoftDeleted('vendors', ['id' => $vendor->id]);
    }

    /** @test */
    public function recreating_a_hidden_vendor_by_name_restores_it_instead_of_duplicating(): void
    {
        $vendor = Vendor::factory()->create(['vendor_name' => 'Nogales Produce']);
        $vendor->delete();

        // vendor_aliases is globally unique on (alias, source), so a second row
        // with the same name would collide. Restoring is the only safe answer.
        $this->actingAs($this->admin())->postJson('/api/vendors', [
            'vendor_name' => 'Nogales Produce',
            'vendor_type' => 'Food',
        ])->assertOk()->assertJsonPath('data.id', $vendor->id);

        $this->assertSame(1, Vendor::withTrashed()->where('vendor_name', 'Nogales Produce')->count());
        $this->assertNotSoftDeleted('vendors', ['id' => $vendor->id]);
    }

    /** @test */
    public function active_status_can_be_toggled_without_deleting(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);

        $this->actingAs($this->admin())->patchJson("/api/vendors/{$vendor->id}/toggle-active")
            ->assertOk()->assertJsonPath('data.is_active', false);
        $this->assertFalse($vendor->fresh()->is_active);
        $this->assertNotSoftDeleted('vendors', ['id' => $vendor->id]);

        $this->actingAs($this->admin())->patchJson("/api/vendors/{$vendor->id}/toggle-active")
            ->assertOk()->assertJsonPath('data.is_active', true);
        $this->assertTrue($vendor->fresh()->is_active);
    }

    /** @test */
    public function hidden_vendors_drop_out_of_the_default_listing(): void
    {
        $admin = $this->admin();
        $visible = Vendor::factory()->create(['vendor_name' => 'Lisanti Foods']);
        $hidden = Vendor::factory()->create(['vendor_name' => 'Sysco Foods']);
        $hidden->delete();

        $this->actingAs($admin)->get(route('admin.vendors.index'))
            ->assertOk()
            ->assertSee('Lisanti Foods')
            ->assertDontSee('Sysco Foods');

        $this->actingAs($admin)->get(route('admin.vendors.index', ['status' => 'hidden']))
            ->assertOk()
            ->assertSee('Sysco Foods')
            ->assertDontSee('Lisanti Foods');
    }

    /** @test */
    public function a_manager_cannot_create_update_or_delete_vendors(): void
    {
        $manager = $this->manager();
        $vendor = Vendor::factory()->create();

        $this->actingAs($manager)->postJson('/api/vendors', [
            'vendor_name' => 'Sneaky Vendor', 'vendor_type' => 'Food',
        ])->assertForbidden();

        $this->actingAs($manager)->putJson("/api/vendors/{$vendor->id}", [
            'vendor_name' => 'Renamed', 'vendor_type' => 'Food',
        ])->assertForbidden();

        $this->actingAs($manager)->deleteJson("/api/vendors/{$vendor->id}")->assertForbidden();
        $this->actingAs($manager)->patchJson("/api/vendors/{$vendor->id}/toggle-active")->assertForbidden();

        $this->assertDatabaseMissing('vendors', ['vendor_name' => 'Sneaky Vendor']);
        $this->assertNotSoftDeleted('vendors', ['id' => $vendor->id]);
    }

    /** @test */
    public function an_owner_cannot_delete_a_vendor(): void
    {
        $vendor = Vendor::factory()->create();

        $this->actingAs($this->owner())->deleteJson("/api/vendors/{$vendor->id}")->assertForbidden();

        $this->assertNotSoftDeleted('vendors', ['id' => $vendor->id]);
    }

    /** @test */
    public function an_owner_cannot_assign_a_vendor_to_a_store_they_do_not_control(): void
    {
        $admin = $this->admin();
        $owner = $this->owner();
        $ownedStore = Store::factory()->create(['created_by' => $admin->id]);
        $foreignStore = Store::factory()->create(['created_by' => $admin->id]);
        $owner->ownedStores()->attach($ownedStore->id);

        $this->actingAs($owner)->postJson('/api/vendors', [
            'vendor_name' => 'Owner Vendor',
            'vendor_type' => 'Food',
            'store_ids' => [$ownedStore->id, $foreignStore->id],
        ])->assertCreated();

        $vendor = Vendor::where('vendor_name', 'Owner Vendor')->firstOrFail();
        $storeIds = $vendor->stores()->pluck('stores.id')->all();

        $this->assertContains($ownedStore->id, $storeIds);
        $this->assertNotContains($foreignStore->id, $storeIds);
    }

    /** @test */
    public function an_admin_can_add_an_alias_to_a_vendor(): void
    {
        $vendor = Vendor::factory()->create();

        $this->actingAs($this->admin())->postJson("/api/vendors/{$vendor->id}/aliases", [
            'alias' => 'LISANTI FOODS INC',
            'source' => 'bank',
        ])->assertCreated();

        $this->assertDatabaseHas('vendor_aliases', [
            'vendor_id' => $vendor->id,
            'alias' => 'LISANTI FOODS INC',
            'source' => 'bank',
        ]);
    }

    /** @test */
    public function adding_an_alias_validates_its_source(): void
    {
        $vendor = Vendor::factory()->create();

        $this->actingAs($this->admin())->postJson("/api/vendors/{$vendor->id}/aliases", [
            'alias' => 'SOMETHING',
            'source' => 'carrier_pigeon',
        ])->assertStatus(422);
    }
}
