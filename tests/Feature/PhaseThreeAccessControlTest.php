<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\DailyReport;
use App\Models\Store;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Phase 3 hardening: cross-store isolation for bank reconciliation, the
 * daily-report delete role gate, vendor store-assignment scoping, and the
 * owner approve_reports permission.
 */
class PhaseThreeAccessControlTest extends TestCase
{
    use RefreshDatabase;

    private function bankTxnForStore(int $storeId): int
    {
        $accountId = DB::table('bank_accounts')->insertGetId([
            'bank_name' => 'Test Bank',
            'account_number_last_four' => '1234',
            'account_type' => 'checking',
            'store_id' => $storeId,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return DB::table('bank_transactions')->insertGetId([
            'bank_account_id' => $accountId,
            'transaction_date' => now()->format('Y-m-d'),
            'transaction_type' => 'debit',
            'amount' => 100.00,
            'duplicate_check_hash' => 'hash-'.$storeId.'-'.uniqid(),
            'reconciliation_status' => 'unmatched',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /** @test */
    public function an_owner_cannot_list_another_stores_bank_transactions(): void
    {
        $ownerA = User::factory()->create(['role' => 'owner']);
        $storeA = Store::factory()->create(['created_by' => $ownerA->id]);
        $txnA = $this->bankTxnForStore($storeA->id);

        $ownerB = User::factory()->create(['role' => 'owner']);
        $storeB = Store::factory()->create(['created_by' => $ownerB->id]);
        $txnB = $this->bankTxnForStore($storeB->id);

        $response = $this->actingAs($ownerA)->getJson('/api/bank/reconciliation');
        $response->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($txnA, $ids, "Owner A should see their own store's transaction");
        $this->assertNotContains($txnB, $ids, "Owner A must NOT see Owner B's transaction");
    }

    /** @test */
    public function an_owner_cannot_reach_another_stores_bank_transaction_by_id(): void
    {
        $ownerA = User::factory()->create(['role' => 'owner']);
        Store::factory()->create(['created_by' => $ownerA->id]);

        $ownerB = User::factory()->create(['role' => 'owner']);
        $storeB = Store::factory()->create(['created_by' => $ownerB->id]);
        $txnB = $this->bankTxnForStore($storeB->id);

        $this->actingAs($ownerA)->getJson("/api/bank/reconciliation/{$txnB}/matches")->assertStatus(403);
        $this->actingAs($ownerA)
            ->postJson("/api/bank/reconciliation/{$txnB}/mark-reviewed", ['notes' => 'x'])
            ->assertStatus(403);
    }

    /** @test */
    public function a_manager_cannot_delete_a_daily_report(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $store = Store::factory()->create(['created_by' => $owner->id]);
        $manager = User::factory()->create(['role' => 'manager']);
        $store->assignedManagers()->attach($manager->id);

        $report = DailyReport::factory()->create([
            'store_id' => $store->id, 'report_date' => now(), 'created_by' => $owner->id,
        ]);

        // Manager has store access (passes daily_report_access) but is blocked by the role gate.
        $this->actingAs($manager)->delete("/daily-reports/{$report->id}")->assertStatus(403);
        $this->assertDatabaseHas('daily_reports', ['id' => $report->id]);

        // Owner may delete.
        $this->actingAs($owner)->delete("/daily-reports/{$report->id}");
        $this->assertDatabaseMissing('daily_reports', ['id' => $report->id]);
    }

    /** @test */
    public function an_owner_cannot_assign_a_vendor_to_a_store_they_do_not_control(): void
    {
        $ownerA = User::factory()->create(['role' => 'owner']);
        $storeA = Store::factory()->create(['created_by' => $ownerA->id]);

        $ownerB = User::factory()->create(['role' => 'owner']);
        $storeB = Store::factory()->create(['created_by' => $ownerB->id]);

        $this->actingAs($ownerA)->postJson('/api/vendors', [
            'vendor_name' => 'Cross Store Vendor',
            'vendor_type' => 'Food',
            'store_ids' => [$storeA->id, $storeB->id],
        ])->assertStatus(201);

        $vendor = Vendor::where('vendor_name', 'Cross Store Vendor')->firstOrFail();
        $storeIds = $vendor->stores()->pluck('stores.id')->all();

        $this->assertContains($storeA->id, $storeIds);
        $this->assertNotContains($storeB->id, $storeIds, 'Owner A must not attach a vendor to Owner B\'s store');
    }

    /** @test */
    public function owners_can_approve_reports(): void
    {
        $this->assertTrue(UserRole::OWNER->hasPermission('approve_reports'));
        $this->assertFalse(UserRole::MANAGER->hasPermission('approve_reports'));
    }
}
