<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoaReportTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /** @test */
    public function the_report_lists_the_whole_chart_by_type_category_and_sub_account(): void
    {
        (new ChartOfAccountsSeeder)->run();

        $response = $this->actingAs($this->admin())->get(route('coa.report'));

        $response->assertStatus(200)
            ->assertSee('Chart of Accounts Report')
            // Type section headers.
            ->assertSee('Expense')
            ->assertSee('Revenue')
            // A category and one of its sub-accounts both appear.
            ->assertSee('Online Merchant Expenses')
            ->assertSee('DoorDash');
    }

    /** @test */
    public function the_report_can_be_filtered_to_a_single_type(): void
    {
        (new ChartOfAccountsSeeder)->run();

        $response = $this->actingAs($this->admin())->get(route('coa.report', ['account_type' => 'Expense']));

        $response->assertStatus(200)
            ->assertSee('Expense accounts')
            ->assertSee('DoorDash')
            // Revenue accounts are excluded when filtered to Expense.
            ->assertDontSee('Revenue - Food Sales');
    }

    /** @test */
    public function the_csv_export_streams_hierarchy_columns(): void
    {
        (new ChartOfAccountsSeeder)->run();

        $response = $this->actingAs($this->admin())->get(route('coa.export.csv'));

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));

        $csv = $response->streamedContent();
        // Header columns (fputcsv may quote fields containing spaces).
        $this->assertStringContainsString('Account Type', $csv);
        $this->assertStringContainsString('Sub-category', $csv);
        $this->assertStringContainsString('Status', $csv);
        // DoorDash is a sub-account: its category column names the Online Merchant parent.
        $this->assertStringContainsString('DoorDash', $csv);
        $this->assertStringContainsString('Online Merchant Expenses', $csv);
    }

    /** @test */
    public function the_report_can_be_scoped_to_a_single_category(): void
    {
        (new ChartOfAccountsSeeder)->run();

        $onlineMerchant = \App\Models\ChartOfAccount::withoutGlobalScopes()->where('account_code', '6450')->first();

        $response = $this->actingAs($this->admin())->get(route('coa.report', [
            'account_type' => 'Expense',
            'category' => $onlineMerchant->id,
        ]));

        $response->assertStatus(200)
            // The chosen category and its sub-accounts show...
            ->assertSee('Online Merchant Expenses')
            ->assertSee('DoorDash')
            // ...but a different category's sub-accounts do not.
            ->assertDontSee('Payroll Taxes');
    }

    /** @test */
    public function the_csv_export_can_be_scoped_to_a_single_category(): void
    {
        (new ChartOfAccountsSeeder)->run();

        $onlineMerchant = \App\Models\ChartOfAccount::withoutGlobalScopes()->where('account_code', '6450')->first();

        $response = $this->actingAs($this->admin())->get(route('coa.export.csv', [
            'account_type' => 'Expense',
            'category' => $onlineMerchant->id,
        ]));

        $response->assertStatus(200);
        $csv = $response->streamedContent();
        $this->assertStringContainsString('DoorDash', $csv);
        $this->assertStringNotContainsString('Payroll', $csv);
    }

    /** @test */
    public function the_pdf_export_returns_a_pdf(): void
    {
        (new ChartOfAccountsSeeder)->run();

        $response = $this->actingAs($this->admin())->get(route('coa.export.pdf'));

        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', strtolower($response->headers->get('content-type')));
    }

    /** @test */
    public function managers_cannot_reach_the_report(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);

        $this->actingAs($manager)->get(route('coa.report'))->assertStatus(403);
    }
}
