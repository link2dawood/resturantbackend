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
    public function the_show_page_has_an_add_sub_account_button_for_a_parent_but_not_a_leaf(): void
    {
        (new ChartOfAccountsSeeder)->run();

        $onlineMerchant = ChartOfAccount::withoutGlobalScopes()->where('account_code', '6450')->first(); // header
        $doordash = ChartOfAccount::withoutGlobalScopes()->where('account_code', '6451')->first();       // leaf

        // A header account offers "Add Sub-Account" linking to the prefilled form.
        $this->actingAs($this->admin())->get(route('coa.show', $onlineMerchant))
            ->assertStatus(200)
            ->assertSee('Add Sub-Account')
            ->assertSee(route('coa.create', ['parent' => $onlineMerchant->id]), false);

        // A detail/leaf account (6451) cannot have children — no button.
        $this->actingAs($this->admin())->get(route('coa.show', $doordash))
            ->assertStatus(200)
            ->assertDontSee('Add Sub-Account');
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
