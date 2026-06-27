<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CoA hierarchy rework: codes are auto-assigned from the selected parent
 * (no free-text entry), preferring the natural block step.
 */
class CoaAutoCodeTest extends TestCase
{
    use RefreshDatabase;

    private function acct(string $code, string $type = 'Expense'): ChartOfAccount
    {
        return ChartOfAccount::create([
            'account_code' => $code, 'account_name' => "Acct {$code}",
            'account_type' => $type, 'is_active' => true,
        ]);
    }

    /** @test */
    public function next_child_code_uses_natural_step_and_skips_used_codes(): void
    {
        // Under a top-level (x000): step 100, 6100/6200 used → 6300.
        $this->acct('6000');
        $this->acct('6100');
        $this->acct('6200');
        $this->assertSame('6300', ChartOfAccount::nextChildCode('6000'));

        // Under a tens header (xyz0): step 1, 6711-6714 used → 6715.
        $this->acct('6710');
        foreach (['6711', '6712', '6713', '6714'] as $c) {
            $this->acct($c);
        }
        $this->assertSame('6715', ChartOfAccount::nextChildCode('6710'));

        // Under a hundreds header (xy00) with no children yet: step 10 → 6810.
        $this->acct('6800');
        $this->assertSame('6810', ChartOfAccount::nextChildCode('6800'));
    }

    /** @test */
    public function store_auto_assigns_the_code_from_the_parent_ignoring_any_posted_code(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $parent = $this->acct('6710');
        foreach (['6711', '6712', '6713', '6714'] as $c) {
            $this->acct($c);
        }

        $this->actingAs($admin)->post(route('coa.store'), [
            'account_type' => 'Expense',
            'account_name' => 'New Platform',
            'parent_account_id' => $parent->id,
            'account_code' => '9999', // should be overridden server-side
            'is_global' => '1',
            'is_active' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('chart_of_accounts', [
            'account_name' => 'New Platform',
            'account_code' => '6715',
            'parent_account_id' => $parent->id,
        ]);
        $this->assertDatabaseMissing('chart_of_accounts', ['account_code' => '9999']);
    }
}
