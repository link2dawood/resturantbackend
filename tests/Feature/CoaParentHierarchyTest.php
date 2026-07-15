<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\User;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The chart hierarchy must be stored on `parent_account_id` (explicit data an
 * admin can change), not inferred from the account number at read time.
 */
class CoaParentHierarchyTest extends TestCase
{
    use RefreshDatabase;

    private function seedChart(): void
    {
        (new ChartOfAccountsSeeder)->run();
    }

    private function id(string $code): int
    {
        return ChartOfAccount::withoutGlobalScopes()->where('account_code', $code)->value('id');
    }

    private function parentCodeOf(string $code): ?string
    {
        $parentId = ChartOfAccount::withoutGlobalScopes()->where('account_code', $code)->value('parent_account_id');

        return $parentId
            ? ChartOfAccount::withoutGlobalScopes()->whereKey($parentId)->value('account_code')
            : null;
    }

    /** @test */
    public function seeding_stores_the_hierarchy_on_parent_account_id(): void
    {
        $this->seedChart();

        // Type roots have no parent.
        $this->assertNull($this->parentCodeOf('6000'));
        $this->assertNull($this->parentCodeOf('4000'));
        $this->assertNull($this->parentCodeOf('5000'));

        // Categories hang off their type root.
        $this->assertSame('6000', $this->parentCodeOf('6400'));
        $this->assertSame('6000', $this->parentCodeOf('6900'));
        $this->assertSame('4000', $this->parentCodeOf('4100'));
        $this->assertSame('5000', $this->parentCodeOf('5400'));

        // Sub-categories hang off their category — including the 3-level branch.
        $this->assertSame('6400', $this->parentCodeOf('6450')); // Online Merchant under Utilities block
        $this->assertSame('6450', $this->parentCodeOf('6451')); // DoorDash under Online Merchant
        $this->assertSame('6900', $this->parentCodeOf('6960')); // Legal & Accounting under Insurance
        $this->assertSame('6600', $this->parentCodeOf('6610')); // Payroll Taxes under Payroll
    }

    /** @test */
    public function revenue_and_cogs_children_are_reachable_via_the_relation(): void
    {
        $this->seedChart();

        $revenue = ChartOfAccount::withoutGlobalScopes()->where('account_code', '4000')->first();
        $cogs = ChartOfAccount::withoutGlobalScopes()->where('account_code', '5000')->first();

        // These were invisible before because parent_account_id was NULL.
        $revenueChildren = $revenue->children()->pluck('account_code')->all();
        $this->assertContains('4010', $revenueChildren); // Cash
        $this->assertContains('4020', $revenueChildren); // Card
        $this->assertContains('4030', $revenueChildren); // Check
        $this->assertContains('4040', $revenueChildren); // Online

        $cogsChildren = $cogs->children()->pluck('account_code')->all();
        $this->assertContains('5100', $cogsChildren);
        $this->assertContains('5400', $cogsChildren); // Alcohol
    }

    /** @test */
    public function an_admin_reparent_is_never_overwritten_by_reseeding(): void
    {
        $this->seedChart();

        // Admin moves an account into a different category by hand.
        $insurance = $this->id('6900');
        ChartOfAccount::withoutGlobalScopes()->where('account_code', '6310')->exists()
            || ChartOfAccount::create([
                'account_code' => '6310', 'account_name' => 'Life Insurance',
                'account_type' => 'Expense', 'is_active' => true,
            ]);
        ChartOfAccount::withoutGlobalScopes()->where('account_code', '6310')
            ->update(['parent_account_id' => $insurance]);

        // Re-seeding must not drag it back under 6300 by its number.
        $this->seedChart();

        $this->assertSame('6900', $this->parentCodeOf('6310'));
    }

    /** @test */
    public function an_admin_can_file_an_account_under_a_category_its_number_does_not_belong_to(): void
    {
        $this->seedChart();

        $admin = User::factory()->create(['role' => 'admin']);

        // 6310's number sits in the Delivery/6300 block, but it's insurance. The
        // admin must be able to file it under Insurance 6900 anyway — this used to
        // be rejected with "the code must be between 6901 and 6999".
        $account = ChartOfAccount::create([
            'account_code' => '6310',
            'account_name' => 'Life Insurance',
            'account_type' => 'Expense',
            'is_active' => true,
        ]);

        $insuranceId = $this->id('6900');

        $response = $this->actingAs($admin)->put(route('coa.update', $account), [
            'account_code' => '6310',
            'account_name' => 'Life Insurance',
            'account_type' => 'Expense',
            'parent_account_id' => $insuranceId,
            'is_global' => 1,
            'is_active' => 1,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame('6900', $this->parentCodeOf('6310'));
    }

    /** @test */
    public function an_account_cannot_be_moved_under_its_own_sub_account(): void
    {
        $this->seedChart();

        $admin = User::factory()->create(['role' => 'admin']);

        $category = ChartOfAccount::withoutGlobalScopes()->where('account_code', '6400')->first();
        $child = ChartOfAccount::withoutGlobalScopes()->where('account_code', '6450')->first();

        // 6450 is a child of 6400; making 6400 a child of 6450 would orphan the branch.
        $response = $this->actingAs($admin)->put(route('coa.update', $category), [
            'account_code' => $category->account_code,
            'account_name' => $category->account_name,
            'account_type' => $category->account_type,
            'parent_account_id' => $child->id,
            'is_global' => 1,
            'is_active' => 1,
        ]);

        $response->assertSessionHasErrors('parent_account_id');
        $this->assertSame('6000', $this->parentCodeOf('6400')); // unchanged
    }

    /** @test */
    public function the_create_and_edit_forms_render_with_the_category_cascade(): void
    {
        $this->seedChart();

        $admin = User::factory()->create(['role' => 'admin']);
        $account = ChartOfAccount::withoutGlobalScopes()->where('account_code', '6451')->first();

        $this->actingAs($admin)->get(route('coa.create'))
            ->assertOk()
            ->assertSee('Category')
            ->assertSee('Sub-category');

        $this->actingAs($admin)->get(route('coa.edit', $account))
            ->assertOk()
            ->assertSee('Category');
    }
}
