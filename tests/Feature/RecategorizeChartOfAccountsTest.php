<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecategorizeChartOfAccountsTest extends TestCase
{
    use RefreshDatabase;

    private function acct(string $code, string $name, ?int $parentId = null): ChartOfAccount
    {
        return ChartOfAccount::create([
            'account_code' => $code,
            'account_name' => $name,
            'account_type' => 'Expense',
            'is_active' => true,
            'parent_account_id' => $parentId,
        ]);
    }

    /** @test */
    public function it_renames_parents_and_refiles_insurance_under_insurance_total(): void
    {
        $root = $this->acct('6000', 'Expenses All');
        $delivery = $this->acct('6300', 'Delivery Service Fees', $root->id);
        $insurance = $this->acct('6900', 'Insurance', $root->id);
        // Miscategorised insurance items, exactly like production.
        $car = $this->acct('6310', 'Car Insurance', $delivery->id);
        $store = $this->acct('6315', 'Store Insurance', $car->id);
        $life = $this->acct('6305', 'Life Insurance', $delivery->id);

        $this->artisan('coa:recategorize', ['--apply' => true])->assertSuccessful();

        // Parent renamed.
        $this->assertSame('Insurance Total', $insurance->fresh()->account_name);
        // Insurance items moved under Insurance Total.
        $this->assertSame($insurance->id, $car->fresh()->parent_account_id);
        $this->assertSame($insurance->id, $store->fresh()->parent_account_id);
        $this->assertSame($insurance->id, $life->fresh()->parent_account_id);
    }

    /** @test */
    public function dry_run_writes_nothing(): void
    {
        $root = $this->acct('6000', 'Expenses All');
        $insurance = $this->acct('6900', 'Insurance', $root->id);

        $this->artisan('coa:recategorize')->assertSuccessful();

        $this->assertSame('Insurance', $insurance->fresh()->account_name);
    }

    /** @test */
    public function missing_codes_are_skipped_without_error(): void
    {
        // Only the root exists; every mapping entry is missing.
        $this->acct('6000', 'Expenses All');

        $this->artisan('coa:recategorize', ['--apply' => true])->assertSuccessful();

        $this->assertTrue(true);
    }
}
