<?php

namespace Tests\Feature;

use App\Models\ExpenseTransaction;
use App\Models\Store;
use App\Models\User;
use App\Support\TenantStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Phase 4 — Multi-tenant isolation.
 *
 * The tenant boundary is the store. These tests prove the TenantScoped global
 * scope keeps one tenant's financial data invisible to another, while admins,
 * the franchisor and background (no-auth) contexts stay unscoped.
 */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $ownerA;
    private User $ownerB;
    private Store $storeA;
    private Store $storeB;
    private int $expenseAId;
    private int $expenseBId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ownerA = User::factory()->create(['role' => 'owner']);
        $this->ownerB = User::factory()->create(['role' => 'owner']);

        // Each owner owns one store (tenant). Ownership via Store.created_by.
        $this->storeA = Store::factory()->create(['created_by' => $this->ownerA->id]);
        $this->storeB = Store::factory()->create(['created_by' => $this->ownerB->id]);

        // One expense per tenant. (No auth user yet → inserts/reads are unscoped.)
        $this->expenseAId = ExpenseTransaction::factory()->create(['store_id' => $this->storeA->id])->id;
        $this->expenseBId = ExpenseTransaction::factory()->create(['store_id' => $this->storeB->id])->id;
    }

    /** @test */
    public function owner_only_sees_their_own_tenant_expenses(): void
    {
        $this->actingAs($this->ownerA);

        $this->assertSame(1, ExpenseTransaction::count(), 'Owner A should only see store A expenses.');
        $this->assertNotNull(ExpenseTransaction::find($this->expenseAId));
        $this->assertNull(ExpenseTransaction::find($this->expenseBId), 'Owner A must NOT see store B data.');
    }

    /** @test */
    public function other_tenant_cannot_load_a_foreign_record_even_by_id(): void
    {
        $this->actingAs($this->ownerB);

        $this->assertNull(ExpenseTransaction::find($this->expenseAId));
        $this->assertSame(1, ExpenseTransaction::count());
        $this->assertNotNull(ExpenseTransaction::find($this->expenseBId));
    }

    /** @test */
    public function admin_is_exempt_and_sees_all_tenants(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $this->assertSame(2, ExpenseTransaction::count());
    }

    /** @test */
    public function franchisor_is_exempt_and_sees_all_tenants(): void
    {
        $franchisor = User::factory()->create(['role' => 'owner', 'name' => 'Franchisor']);
        $this->actingAs($franchisor);

        $this->assertSame(2, ExpenseTransaction::count());
    }

    /** @test */
    public function background_no_auth_context_is_not_scoped(): void
    {
        // No actingAs() — e.g. console commands, queue jobs, Stripe webhooks.
        $this->assertSame(2, ExpenseTransaction::count());
    }

    /** @test */
    public function without_tenant_scope_escape_hatch_bypasses_isolation(): void
    {
        $this->actingAs($this->ownerA);

        $this->assertSame(2, ExpenseTransaction::withoutTenantScope()->count());
    }

    /** @test */
    public function uploads_are_written_under_a_tenant_scoped_path(): void
    {
        Storage::fake('local');

        $path = TenantStorage::storeUpload(
            UploadedFile::fake()->create('statement.csv', 10),
            'owner_cc_statements',
            $this->storeA->id,
            'doc.csv',
            'local'
        );

        $this->assertSame("tenant/{$this->storeA->id}/owner_cc_statements/doc.csv", $path);
        Storage::disk('local')->assertExists($path);
        $this->assertSame('tenant/shared', TenantStorage::prefix(null));
    }
}
