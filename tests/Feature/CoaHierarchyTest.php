<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\User;
use App\Services\CoaTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CoA hierarchy improvements: type/parent code restrictions, rollup warnings,
 * child auto-display, and the new Alcohol category.
 */
class CoaHierarchyTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        return User::factory()->create(['role' => 'owner']);
    }

    private function coa(string $code): ChartOfAccount
    {
        return ChartOfAccount::where('account_code', $code)->firstOrFail();
    }

    /** @test */
    public function account_code_must_be_within_the_type_range(): void
    {
        // 5500 is a COGS-range code; an Expense account must be 6000-6999.
        $this->actingAs($this->owner())->postJson('/api/coa', [
            'account_code' => '5500',
            'account_name' => 'Wrong range',
            'account_type' => 'Expense',
        ])->assertStatus(422)->assertJsonValidationErrors('account_code');
    }

    /** @test */
    public function child_code_must_be_within_the_parent_sub_range(): void
    {
        $parent = $this->coa('6450'); // Online Merchant — children must be 6451-6459

        $this->actingAs($this->owner())->postJson('/api/coa', [
            'account_code' => '6470',
            'account_name' => 'Out of parent block',
            'account_type' => 'Expense',
            'parent_account_id' => $parent->id,
        ])->assertStatus(422)->assertJsonValidationErrors('account_code');
    }

    /** @test */
    public function a_valid_sub_account_is_accepted(): void
    {
        $parent = $this->coa('6450');

        $this->actingAs($this->owner())->postJson('/api/coa', [
            'account_code' => '6455',
            'account_name' => 'New Platform',
            'account_type' => 'Expense',
            'parent_account_id' => $parent->id,
        ])->assertSuccessful();

        $this->assertDatabaseHas('chart_of_accounts', ['account_code' => '6455', 'account_name' => 'New Platform']);
    }

    /** @test */
    public function child_type_must_match_parent_type(): void
    {
        $parent = $this->coa('6450'); // Expense

        // Code 5455 is COGS-range; declaring COGS makes the range valid but the
        // parent (Expense) type then mismatches.
        $this->actingAs($this->owner())->postJson('/api/coa', [
            'account_code' => '5455',
            'account_name' => 'Mismatch',
            'account_type' => 'COGS',
            'parent_account_id' => $parent->id,
        ])->assertStatus(422);
    }

    /** @test */
    public function children_endpoint_lists_block_children_and_range(): void
    {
        $parent = $this->coa('6450');

        $response = $this->actingAs($this->owner())->getJson("/api/coa/{$parent->id}/children");

        $response->assertOk()
            ->assertJsonPath('is_rollup_total', true)
            ->assertJsonPath('child_range', [6451, 6459]);

        $codes = collect($response->json('children'))->pluck('account_code')->all();
        foreach (['6451', '6452', '6453', '6454'] as $code) {
            $this->assertContains($code, $codes, "Online Merchant children should include {$code}.");
        }
    }

    /** @test */
    public function adding_an_account_that_rolls_up_to_a_total_flashes_a_warning(): void
    {
        // 6201 infers parent 6200, which is a rollup total.
        $this->actingAs($this->owner())->post(route('coa.store'), [
            'account_code' => '6201',
            'account_name' => 'Rolls up',
            'account_type' => 'Expense',
            'is_global' => '1',
        ])->assertRedirect(route('coa.index'))->assertSessionHas('warning');
    }

    /** @test */
    public function alcohol_5400_exists_under_cost_of_goods_sold(): void
    {
        // Added by the migration (and the CoA template).
        $this->assertDatabaseHas('chart_of_accounts', [
            'account_code' => '5400',
            'account_name' => 'Alcohol',
            'account_type' => 'COGS',
        ]);

        $codes = array_column(app(CoaTemplateService::class)->defaultAccounts(), 'account_code');
        $this->assertContains('5400', $codes);
    }
}
