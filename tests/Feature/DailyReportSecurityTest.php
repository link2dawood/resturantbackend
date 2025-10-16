<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Store;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyReportSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Disable CSRF middleware for testing
        $this->withoutMiddleware();

        // Create users for testing
        $this->owner = User::factory()->create([
            'role' => UserRole::OWNER,
            'email' => 'owner@test.com',
        ]);

        $this->manager1 = User::factory()->create([
            'role' => UserRole::MANAGER,
            'email' => 'manager1@test.com',
        ]);

        $this->manager2 = User::factory()->create([
            'role' => UserRole::MANAGER,
            'email' => 'manager2@test.com',
        ]);

        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'email' => 'admin@test.com',
        ]);

        // Create stores
        $this->ownerStore = Store::factory()->create([
            'created_by' => $this->owner->id,
            'store_info' => 'Owner Store',
        ]);

        $this->unassignedStore = Store::factory()->create([
            'created_by' => $this->owner->id,
            'store_info' => 'Unassigned Store',
        ]);

        // Assign manager1 to ownerStore
        $this->ownerStore->managers()->attach($this->manager1->id);
        // manager2 has no store assignments
    }

    /** @test */
    public function manager_can_only_see_assigned_stores_in_create_form()
    {
        // Test manager1 can see assigned store
        $response = $this->actingAs($this->manager1)->get('/daily-reports/create');
        $response->assertStatus(200);

        $stores = $response->viewData('stores');
        $this->assertCount(1, $stores);
        $this->assertEquals($this->ownerStore->id, $stores->first()->id);
    }

    /** @test */
    public function manager_with_no_assignments_sees_empty_store_list()
    {
        // Test manager2 sees empty list
        $response = $this->actingAs($this->manager2)->get('/daily-reports/create');
        $response->assertStatus(200);

        $stores = $response->viewData('stores');
        $this->assertCount(0, $stores);
    }

    /** @test */
    public function manager_cannot_create_report_for_unassigned_store()
    {
        $reportData = [
            'store_id' => $this->unassignedStore->id,
            'report_date' => Carbon::today()->format('Y-m-d'),
            'projected_sales' => 1000.00,
            'gross_sales' => 1200.00,
            'total_paid_outs' => 100.00,
        ];

        $response = $this->actingAs($this->manager1)->post('/daily-reports', $reportData);

        // Debug the response
        if (! session()->has('errors')) {
            $this->fail('Expected validation errors but got status: '.$response->getStatusCode());
        }

        $response->assertSessionHasErrors(['store_id']);
        $this->assertStringContainsString('You are not authorized to create reports for this store.',
            session('errors')->get('store_id')[0]);
    }

    /** @test */
    public function manager_can_create_report_for_assigned_store()
    {
        $reportData = [
            'store_id' => $this->ownerStore->id,
            'report_date' => Carbon::today()->format('Y-m-d'),
            'projected_sales' => 1000.00,
            'gross_sales' => 1200.00,
            'total_paid_outs' => 100.00,
        ];

        $response = $this->actingAs($this->manager1)->post('/daily-reports', $reportData);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('daily_reports', [
            'store_id' => $this->ownerStore->id,
            'created_by' => $this->manager1->id,
        ]);
    }

    /** @test */
    public function owner_can_only_create_reports_for_owned_stores()
    {
        $otherOwnerStore = Store::factory()->create([
            'created_by' => $this->admin->id, // Different owner
            'store_info' => 'Other Owner Store',
        ]);

        $reportData = [
            'store_id' => $otherOwnerStore->id,
            'report_date' => Carbon::today()->format('Y-m-d'),
            'projected_sales' => 1000.00,
            'gross_sales' => 1200.00,
            'total_paid_outs' => 100.00,
        ];

        $response = $this->actingAs($this->owner)->post('/daily-reports', $reportData);

        $response->assertSessionHasErrors(['store_id']);
        $this->assertStringContainsString('You are not authorized to create reports for this store.',
            session('errors')->get('store_id')[0]);
    }

    /** @test */
    public function admin_can_create_reports_for_any_store()
    {
        $reportData = [
            'store_id' => $this->ownerStore->id,
            'report_date' => Carbon::today()->format('Y-m-d'),
            'projected_sales' => 1000.00,
            'gross_sales' => 1200.00,
            'total_paid_outs' => 100.00,
        ];

        $response = $this->actingAs($this->admin)->post('/daily-reports', $reportData);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('daily_reports', [
            'store_id' => $this->ownerStore->id,
            'created_by' => $this->admin->id,
        ]);
    }
}
