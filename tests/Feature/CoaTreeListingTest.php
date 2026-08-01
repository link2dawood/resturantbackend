<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\User;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoaTreeListingTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /** @test */
    public function the_listing_renders_the_full_hierarchy_without_collapse_controls(): void
    {
        (new ChartOfAccountsSeeder)->run();

        $response = $this->actingAs($this->admin())->get(route('coa.index'));

        $response->assertStatus(200)
            ->assertSee('id="coaTreeBody"', false)
            // A category with children (Online Merchant 6450) shows a sub-account badge.
            ->assertSee('sub-account')
            // Sub-accounts are shown inline (no expand/collapse).
            ->assertSee('DoorDash')
            ->assertDontSee('Expand all')
            ->assertDontSee('Collapse all');
    }

    /** @test */
    public function each_parent_has_an_add_sub_account_link_to_the_prefilled_form(): void
    {
        (new ChartOfAccountsSeeder)->run();

        $utilities = ChartOfAccount::withoutGlobalScopes()->where('account_code', '6400')->first();

        $response = $this->actingAs($this->admin())->get(route('coa.index'));
        $response->assertStatus(200)
            ->assertSee(route('coa.create', ['parent' => $utilities->id]), false);
    }

    /** @test */
    public function the_create_form_preselects_the_parent_from_the_query_string(): void
    {
        (new ChartOfAccountsSeeder)->run();

        $insurance = ChartOfAccount::withoutGlobalScopes()->where('account_code', '6900')->first();

        $response = $this->actingAs($this->admin())->get(route('coa.create', ['parent' => $insurance->id]));

        $response->assertStatus(200)
            ->assertSee('PRESELECT_PARENT_ID', false)
            ->assertSee($insurance->id, false);
    }

    /** @test */
    public function the_show_page_offers_add_sub_account_on_every_account(): void
    {
        (new ChartOfAccountsSeeder)->run();

        $onlineMerchant = ChartOfAccount::withoutGlobalScopes()->where('account_code', '6450')->first(); // header
        $doordash = ChartOfAccount::withoutGlobalScopes()->where('account_code', '6451')->first();       // leaf

        // Bug 1: the "Add Sub-Account" button must appear consistently — on a header
        // account AND on what used to be a "leaf" (any account can be a parent now).
        foreach ([$onlineMerchant, $doordash] as $account) {
            $this->actingAs($this->admin())->get(route('coa.show', $account))
                ->assertStatus(200)
                ->assertSee('Add Sub-Account')
                ->assertSee(route('coa.create', ['parent' => $account->id]), false);
        }
    }

    /** @test */
    public function an_admin_can_create_a_new_top_level_category_under_expenses(): void
    {
        (new ChartOfAccountsSeeder)->run();

        $expensesRoot = ChartOfAccount::withoutGlobalScopes()->where('account_code', '6000')->first();

        // The "New top-level category" option posts the type root as the parent;
        // the server derives the next X00 code.
        $response = $this->actingAs($this->admin())->post(route('coa.store'), [
            'account_type' => 'Expense',
            'account_name' => 'Brand New Category',
            'parent_account_id' => $expensesRoot->id,
            'is_global' => 1,
            'is_active' => 1,
        ]);

        $response->assertSessionHasNoErrors();

        $created = ChartOfAccount::withoutGlobalScopes()->where('account_name', 'Brand New Category')->first();
        $this->assertNotNull($created);
        $this->assertSame('Expense', $created->account_type);
        // It sits directly under Expenses (a real category), not detached.
        $this->assertSame($expensesRoot->id, (int) $created->parent_account_id);
        // ...with a code inside the Expense range (a clean X00 when one is free,
        // else the next free code — either way it's a top-level category).
        $this->assertGreaterThanOrEqual(6001, (int) $created->account_code);
        $this->assertLessThanOrEqual(6999, (int) $created->account_code);
    }

    /** @test */
    public function a_sub_account_can_be_created_under_a_parent_whose_code_has_no_natural_range(): void
    {
        $admin = $this->admin();
        $root = ChartOfAccount::create(['account_code' => '6000', 'account_name' => 'Expenses All', 'account_type' => 'Expense', 'is_active' => true]);
        // 6305 ends in 5 — the old code rule said it "cannot be a parent" (Bug 1).
        $parent = ChartOfAccount::create(['account_code' => '6305', 'account_name' => 'Banking Fees Total', 'account_type' => 'Expense', 'is_active' => true, 'parent_account_id' => $root->id]);

        $response = $this->actingAs($admin)->post(route('coa.store'), [
            'account_type' => 'Expense',
            'account_name' => 'Wire Transfer Fees',
            'parent_account_id' => $parent->id,
            'is_global' => 1,
            'is_active' => 1,
        ]);

        $response->assertSessionHasNoErrors();
        $child = ChartOfAccount::withoutGlobalScopes()->where('account_name', 'Wire Transfer Fees')->first();
        $this->assertNotNull($child, 'A child should be creatable under any parent');
        $this->assertSame($parent->id, (int) $child->parent_account_id);
        // Code was derived inside the Expense block.
        $this->assertGreaterThanOrEqual(6001, (int) $child->account_code);
        $this->assertLessThanOrEqual(6999, (int) $child->account_code);
    }

    /** @test */
    public function the_create_form_offers_a_new_top_level_category_option(): void
    {
        (new ChartOfAccountsSeeder)->run();

        $this->actingAs($this->admin())->get(route('coa.create'))
            ->assertStatus(200)
            ->assertSee('New top-level category', false);
    }

    /** @test */
    public function searching_returns_a_flat_list_not_the_tree(): void
    {
        (new ChartOfAccountsSeeder)->run();

        $response = $this->actingAs($this->admin())->get(route('coa.index', ['search' => 'DoorDash']));

        $response->assertStatus(200)
            ->assertSee('DoorDash')
            // Flat mode does not render the tree body.
            ->assertDontSee('id="coaTreeBody"', false);
    }
}
