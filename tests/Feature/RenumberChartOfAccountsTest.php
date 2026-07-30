<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RenumberChartOfAccountsTest extends TestCase
{
    use RefreshDatabase;

    private function acct(string $code, string $name, ?int $parentId = null): ChartOfAccount
    {
        // updateOrCreate: a migration seeds 6450/6451-6454, so reuse rather than collide.
        return ChartOfAccount::updateOrCreate(
            ['account_code' => $code],
            [
                'account_name' => $name,
                'account_type' => 'Expense',
                'is_active' => true,
                'parent_account_id' => $parentId,
            ]
        );
    }

    /** @test */
    public function it_moves_a_mismatched_code_into_its_parent_range_and_keeps_fitting_ones(): void
    {
        $root = $this->acct('6000', 'Expenses All');
        $insurance = $this->acct('6900', 'Insurance Total', $root->id);
        // 6305 is under Insurance but its number is in the 63xx block — mismatch.
        $life = $this->acct('6305', 'Life Insurance', $insurance->id);
        // A utilities sub-account that already fits stays put.
        $utilities = $this->acct('6400', 'Utilities Total', $root->id);
        $water = $this->acct('6410', 'Water', $utilities->id);

        $this->artisan('coa:renumber', ['--apply' => true])->assertSuccessful();

        $newLife = (int) $life->fresh()->account_code;
        $this->assertGreaterThanOrEqual(6901, $newLife, 'Life Insurance should move into 69xx');
        $this->assertLessThanOrEqual(6999, $newLife);
        // The already-correct code is untouched.
        $this->assertSame('6410', $water->fresh()->account_code);
        // Category codes (direct children of the root) that already fit stay put.
        $this->assertSame('6900', $insurance->fresh()->account_code);
    }

    /** @test */
    public function wired_in_codes_are_never_moved(): void
    {
        $root = $this->acct('6000', 'Expenses All');
        $online = $this->acct('6450', 'Online Merchant Expenses Total', $root->id);
        $doordash = $this->acct('6451', 'DoorDash', $online->id);

        $this->artisan('coa:renumber', ['--apply' => true])->assertSuccessful();

        $this->assertSame('6451', $doordash->fresh()->account_code);
        $this->assertSame('6100', '6100'); // sanity
    }

    /** @test */
    public function a_full_block_is_left_as_is(): void
    {
        $root = $this->acct('6000', 'Expenses All');
        $online = $this->acct('6450', 'Online Merchant Total', $root->id);
        // Fill 6451-6459 (9 slots) then add two overflow accounts numbered elsewhere.
        for ($i = 1; $i <= 9; $i++) {
            $this->acct('645'.$i, 'Platform '.$i, $online->id);
        }
        $overflow = $this->acct('6460', 'Relish', $online->id);

        $this->artisan('coa:renumber', ['--apply' => true])->assertSuccessful();

        // No room in 6451-6459, so it stays 6460 rather than colliding.
        $this->assertSame('6460', $overflow->fresh()->account_code);
    }

    /** @test */
    public function dry_run_writes_nothing(): void
    {
        $root = $this->acct('6000', 'Expenses All');
        $insurance = $this->acct('6900', 'Insurance Total', $root->id);
        $life = $this->acct('6305', 'Life Insurance', $insurance->id);

        $this->artisan('coa:renumber')->assertSuccessful();

        $this->assertSame('6305', $life->fresh()->account_code);
    }
}
