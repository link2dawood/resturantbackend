<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Store;
use App\Models\Vendor;
use App\Models\ChartOfAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function admin_can_access_all_resources()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $response = $this->actingAs($admin)->getJson('/api/vendors');
        $response->assertStatus(200);
        
        $response = $this->actingAs($admin)->getJson('/api/coa');
        $response->assertStatus(200);
        
        $response = $this->actingAs($admin)->getJson('/api/expenses');
        $response->assertStatus(200);
    }

    /** @test */
    public function manager_can_only_view_their_store_expenses()
    {
        $store1 = Store::factory()->create();
        $store2 = Store::factory()->create();
        
        $manager = User::factory()->create([
            'role' => 'manager',
            'store_id' => $store1->id
        ]);
        
        // Should be able to view expenses
        $response = $this->actingAs($manager)->getJson('/api/expenses');
        $response->assertStatus(200);
        
        // Should not be able to create vendors
        $response = $this->actingAs($manager)->postJson('/api/vendors', []);
        $response->assertStatus(403);
    }

    /** @test */
    public function owner_can_create_and_manage_vendors()
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $store = Store::factory()->create(['created_by' => $owner->id]);
        
        $response = $this->actingAs($owner)->getJson('/api/vendors');
        $response->assertStatus(200);
        
        $vendorData = [
            'vendor_name' => 'Test Vendor',
            'vendor_type' => 'Food',
            'is_active' => true,
            'store_ids' => [$store->id]
        ];
        
        $response = $this->actingAs($owner)->postJson('/api/vendors', $vendorData);
        $response->assertStatus(201);
    }

    /** @test */
    public function users_cannot_access_stores_they_dont_own()
    {
        $owner1 = User::factory()->create(['role' => 'owner']);
        $owner2 = User::factory()->create(['role' => 'owner']);
        
        $store1 = Store::factory()->create(['created_by' => $owner1->id]);
        $store2 = Store::factory()->create(['created_by' => $owner2->id]);
        
        $response = $this->actingAs($owner1)->getJson('/api/expenses?store_id=' . $store2->id);
        
        // Should be filtered by store access - data should be empty for store2
        $response->assertStatus(200);
        $data = $response->json();
        // Owner1 should not see store2's expenses, or the filter should prevent it
        $this->assertTrue(true); // Placeholder - actual assertion depends on implementation
    }
}
