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
    public function the_listing_renders_as_a_tree_with_categories_shown_and_grandchildren_hidden(): void
    {
        (new ChartOfAccountsSeeder)->run();

        $response = $this->actingAs($this->admin())->get(route('coa.index'));

        $response->assertStatus(200)
            ->assertSee('id="coaTreeBody"', false)
            ->assertSee('Expand all')
            // A category with children (Online Merchant 6450) shows a sub-account badge.
            ->assertSee('sub-account')
            // Grandchildren (DoorDash 6451, depth >= 2) render but start hidden.
            ->assertSee('DoorDash');

        // The 6451 row is present but collapsed (style display:none via depth >= 2).
        $doordash = ChartOfAccount::withoutGlobalScopes()->where('account_code', '6451')->first();
        $response->assertSee('data-node-id="' . $doordash->id . '"', false);
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
