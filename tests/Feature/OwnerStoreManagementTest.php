<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Owners can manage the store(s) they own (e.g. the one created at signup):
 * view + edit their own, but not other tenants' stores, and not the
 * admin-only actions (create/delete/reassign ownership).
 */
class OwnerStoreManagementTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $owner->startTrial(); // pass the trial gate

        return $owner;
    }

    /** @test */
    public function owner_can_open_the_edit_form_for_their_own_store(): void
    {
        $owner = $this->owner();
        $store = Store::factory()->create(['created_by' => $owner->id, 'store_type' => 'franchisee']);

        $this->actingAs($owner)->get(route('stores.index'))->assertOk();
        $this->actingAs($owner)->get(route('stores.edit', $store))->assertOk();
    }

    /** @test */
    public function owner_can_update_their_own_store_information(): void
    {
        $owner = $this->owner();
        $store = Store::factory()->create(['created_by' => $owner->id, 'store_type' => 'franchisee']);

        $this->actingAs($owner)->put(route('stores.update', $store), [
            'store_info' => 'Renamed Diner',
            'contact_name' => 'Jane Owner',
            'phone' => '(555) 123-4567',
            'address' => '1 New St',
            'city' => 'Philadelphia',
            'state' => 'PA',
            'zip' => '19103',
            'store_type' => 'franchisee',
            'created_by' => $owner->id,
            'sales_tax_rate' => 8,
            'medicare_tax_rate' => 1.45,
        ])->assertRedirect();

        $this->assertSame('Renamed Diner', $store->fresh()->store_info);
    }

    /** @test */
    public function owner_cannot_reach_another_owners_store(): void
    {
        $owner = $this->owner();
        $otherStore = Store::factory()->create([
            'created_by' => User::factory()->create(['role' => 'owner'])->id,
            'store_type' => 'franchisee',
        ]);

        $this->actingAs($owner)->get(route('stores.edit', $otherStore))->assertForbidden();
    }

    /** @test */
    public function owner_cannot_create_or_delete_stores_directly(): void
    {
        $owner = $this->owner();
        $store = Store::factory()->create(['created_by' => $owner->id, 'store_type' => 'franchisee']);

        $this->actingAs($owner)->get(route('stores.create'))->assertForbidden();
        $this->actingAs($owner)->delete(route('stores.destroy', $store))->assertForbidden();
    }
}
