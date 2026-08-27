<?php

namespace Tests\Feature;

use App\Models\CategorizationDecision;
use App\Models\TransactionMappingRule;
use App\Models\User;
use App\Services\CategorizationEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Phase 4 categorization engine: fuzzy matching accuracy, per-client (owner)
 * rule isolation, global fallback, learning, and decision logging.
 */
class CategorizationEngineTest extends TestCase
{
    use RefreshDatabase;

    private CategorizationEngine $engine;
    private int $coaFood;
    private int $coaSupplies;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = app(CategorizationEngine::class);
        $this->coaFood = $this->coa('5001', 'COGS - Food', 'COGS');
        $this->coaSupplies = $this->coa('6001', 'Supplies', 'Expense');
    }

    private function coa(string $code, string $name, string $type): int
    {
        return DB::table('chart_of_accounts')->insertGetId([
            'account_code' => $code, 'account_name' => $name, 'account_type' => $type,
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function rule(?int $ownerId, string $pattern, int $coaId, float $confidence = 0.90): TransactionMappingRule
    {
        return TransactionMappingRule::create([
            'owner_id' => $ownerId,
            'description_pattern' => $pattern,
            'coa_id' => $coaId,
            'confidence_score' => $confidence,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_fuzzy_matches_a_merchant_variant_to_the_learned_rule(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->rule($owner->id, 'SAMS CLUB', $this->coaFood);

        // A messier real-world line should still resolve to the same COA.
        $suggestion = $this->engine->suggest('SAMS CLUB #4160 HOUSTON TX', $owner->id);

        $this->assertNotNull($suggestion);
        $this->assertSame($this->coaFood, $suggestion['coa_id']);
        $this->assertGreaterThanOrEqual(CategorizationEngine::SUGGEST_THRESHOLD, $suggestion['confidence']);
    }

    /** @test */
    public function rules_are_isolated_per_client(): void
    {
        $ownerA = User::factory()->create(['role' => 'owner']);
        $ownerB = User::factory()->create(['role' => 'owner']);
        $this->rule($ownerA->id, 'SYSCO FOODS', $this->coaFood);

        // Owner B never taught this rule and it isn't global → no suggestion.
        $this->assertNull($this->engine->suggest('SYSCO FOODS DELIVERY', $ownerB->id));
        // Owner A still gets it.
        $this->assertNotNull($this->engine->suggest('SYSCO FOODS DELIVERY', $ownerA->id));
    }

    /** @test */
    public function a_global_rule_is_a_fallback_for_any_client(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->rule(null, 'COSTCO WHOLESALE', $this->coaSupplies); // owner_id NULL = global

        $suggestion = $this->engine->suggest('COSTCO WHOLESALE #123', $owner->id);

        $this->assertNotNull($suggestion);
        $this->assertSame($this->coaSupplies, $suggestion['coa_id']);
    }

    /** @test */
    public function learning_creates_an_owner_scoped_rule(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);

        $rule = $this->engine->learn('RESTAURANT DEPOT', $this->coaFood, $owner->id);

        $this->assertDatabaseHas('transaction_mapping_rules', [
            'id' => $rule->id, 'owner_id' => $owner->id,
            'description_pattern' => 'RESTAURANT DEPOT', 'coa_id' => $this->coaFood,
        ]);
    }

    /** @test */
    public function every_suggestion_and_learn_is_logged(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->rule($owner->id, 'SAMS CLUB', $this->coaFood);

        $this->engine->suggest('SAMS CLUB #4160', $owner->id);
        $this->engine->suggest('TOTALLY UNKNOWN VENDOR XYZ', $owner->id);
        $this->engine->learn('NEW VENDOR', $this->coaSupplies, $owner->id);

        $this->assertSame(1, CategorizationDecision::where('decision', 'suggested')->whereNotNull('suggested_coa_id')->count());
        $this->assertSame(1, CategorizationDecision::where('decision', 'suggested')->where('source', 'none')->count());
        $this->assertSame(1, CategorizationDecision::where('decision', 'learned')->count());
    }

    /** @test */
    public function it_returns_nothing_when_no_rule_is_close_enough(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->rule($owner->id, 'SHELL GAS STATION', $this->coaSupplies);

        $this->assertNull($this->engine->suggest('AMAZON WEB SERVICES', $owner->id));
    }
}
