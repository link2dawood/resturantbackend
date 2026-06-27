<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 4 — Role-Based Access Control.
 *
 * Focuses on the one rule that differs from the app's existing RBAC: an OWNER
 * may ADD chart-of-accounts but may NOT modify existing ones (admin-only).
 * Admin = full CoA control; Manager = no CoA management.
 */
class RoleBasedAccessControlTest extends TestCase
{
    use RefreshDatabase;

    private function coa(string $code = '6155'): ChartOfAccount
    {
        return ChartOfAccount::create([
            'account_code' => $code,
            'account_name' => "Acct {$code}",
            'account_type' => 'Expense',
            'is_active' => true,
        ]);
    }

    // --- Owner: can ADD ---------------------------------------------------

    /** @test */
    public function owner_can_add_a_chart_of_account(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);

        $this->actingAs($owner)->postJson('/api/coa', [
            'account_code' => '6160',
            'account_name' => 'New Account',
            'account_type' => 'Expense',
        ])->assertSuccessful();

        $this->assertDatabaseHas('chart_of_accounts', ['account_code' => '6160']);
    }

    // --- Owner: cannot MODIFY existing -----------------------------------

    /** @test */
    public function owner_cannot_update_an_existing_chart_of_account_via_api(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $coa = $this->coa();

        $this->actingAs($owner)->putJson("/api/coa/{$coa->id}", [
            'account_code' => '6155',
            'account_name' => 'Hacked',
            'account_type' => 'Expense',
        ])->assertForbidden();

        $this->actingAs($owner)->deleteJson("/api/coa/{$coa->id}")->assertForbidden();
    }

    /** @test */
    public function owner_can_edit_and_delete_accounts_they_created(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $own = ChartOfAccount::create([
            'account_code' => '6160', 'account_name' => 'Owner Acct', 'account_type' => 'Expense',
            'is_active' => true, 'is_system_account' => false, 'created_by' => $owner->id,
        ]);

        $this->actingAs($owner)->get(route('coa.edit', $own))->assertOk();
        $this->actingAs($owner)->delete(route('coa.destroy', $own))->assertRedirect();
        $this->assertDatabaseMissing('chart_of_accounts', ['id' => $own->id]);
    }

    /** @test */
    public function owner_cannot_edit_or_delete_seeded_or_others_accounts(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $foreign = $this->coa(); // created_by null (seeded / admin)

        $this->actingAs($owner)->get(route('coa.edit', $foreign))->assertForbidden();
        $this->actingAs($owner)->put(route('coa.update', $foreign), [])->assertForbidden();
        $this->actingAs($owner)->delete(route('coa.destroy', $foreign))->assertForbidden();
    }

    // --- Admin: full control ---------------------------------------------

    /** @test */
    public function admin_can_update_an_existing_chart_of_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $coa = $this->coa();

        $this->actingAs($admin)->putJson("/api/coa/{$coa->id}", [
            'account_code' => '6155',
            'account_name' => 'Renamed',
            'account_type' => 'Expense',
        ])->assertSuccessful();

        $this->assertDatabaseHas('chart_of_accounts', ['id' => $coa->id, 'account_name' => 'Renamed']);
    }

    // --- Manager: no CoA management --------------------------------------

    /** @test */
    public function manager_cannot_add_a_chart_of_account(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);

        $this->actingAs($manager)->postJson('/api/coa', [
            'account_code' => '6170',
            'account_name' => 'Nope',
            'account_type' => 'Expense',
        ])->assertForbidden();

        // And the web add form is off-limits too (role:admin,owner).
        $this->actingAs($manager)->get(route('coa.create'))->assertForbidden();
    }
}
