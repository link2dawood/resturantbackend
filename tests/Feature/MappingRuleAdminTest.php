<?php

namespace Tests\Feature;

use App\Models\TransactionMappingRule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Phase 4 rules admin view: access, and per-client authorization (an owner may
 * only manage their own rules).
 */
class MappingRuleAdminTest extends TestCase
{
    use RefreshDatabase;

    private int $coaId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->coaId = DB::table('chart_of_accounts')->insertGetId([
            'account_code' => '6001', 'account_name' => 'Supplies', 'account_type' => 'Expense',
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function ruleFor(?int $ownerId): TransactionMappingRule
    {
        return TransactionMappingRule::create([
            'owner_id' => $ownerId, 'description_pattern' => 'SAMS CLUB',
            'coa_id' => $this->coaId, 'confidence_score' => 0.9, 'is_active' => true,
        ]);
    }

    /** @test */
    public function an_owner_can_view_the_rules_page(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->ruleFor($owner->id);

        $this->actingAs($owner)->get('/mapping-rules')
            ->assertOk()
            ->assertSee('Categorization Rules');
    }

    /** @test */
    public function an_owner_can_toggle_their_own_rule(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $rule = $this->ruleFor($owner->id);

        $this->actingAs($owner)->patch("/mapping-rules/{$rule->id}/toggle");

        $this->assertFalse($rule->fresh()->is_active);
    }

    /** @test */
    public function an_owner_cannot_manage_another_owners_rule(): void
    {
        $ownerA = User::factory()->create(['role' => 'owner']);
        $ownerB = User::factory()->create(['role' => 'owner']);
        $rule = $this->ruleFor($ownerA->id);

        $this->actingAs($ownerB)->patch("/mapping-rules/{$rule->id}/toggle")->assertStatus(403);
        $this->assertTrue($rule->fresh()->is_active, 'Rule must be untouched');

        $this->actingAs($ownerB)->delete("/mapping-rules/{$rule->id}")->assertStatus(403);
        $this->assertDatabaseHas('transaction_mapping_rules', ['id' => $rule->id]);
    }

    /** @test */
    public function an_admin_can_update_any_rule(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'owner']);
        $rule = $this->ruleFor($owner->id);

        $this->actingAs($admin)->put("/mapping-rules/{$rule->id}", [
            'description_pattern' => 'SAMS CLUB WAREHOUSE',
            'coa_id' => $this->coaId,
            'is_active' => '1',
        ]);

        $this->assertSame('SAMS CLUB WAREHOUSE', $rule->fresh()->description_pattern);
    }
}
