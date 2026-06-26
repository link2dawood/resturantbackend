<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ChartOfAccount;
use App\Models\Store;
use App\Models\TransactionType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Daily report: assigning a vendor/description a chart of account is remembered
 * for next time (persisted as that description's default COA, which auto-fills
 * the Transaction Type on later reports).
 */
class DailyReportVendorMemoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
    }

    private function reportPayload(int $storeId, array $transactions): array
    {
        return [
            'store_id' => $storeId,
            'report_date' => Carbon::today()->format('Y-m-d'),
            'projected_sales' => 1000.00,
            'gross_sales' => 1200.00,
            'total_paid_outs' => 100.00,
            'total_customers' => 50,
            'credit_cards' => 800.00,
            'actual_deposit' => 1200.00,
            'transactions' => $transactions,
        ];
    }

    /** @test */
    public function assigning_a_description_a_coa_is_remembered_as_its_default(): void
    {
        $owner = User::factory()->create(['role' => UserRole::OWNER]);
        $store = Store::factory()->create(['created_by' => $owner->id]);
        $coa = ChartOfAccount::create([
            'account_code' => '6300', 'account_name' => 'Supplies', 'account_type' => 'Expense', 'is_active' => true,
        ]);

        $this->actingAs($owner)->post('/daily-reports', $this->reportPayload($store->id, [
            ['transaction_id' => 1, 'company' => 'Sysco Foods', 'transaction_type' => $coa->id, 'amount' => 120.50],
        ]))->assertSessionDoesntHaveErrors();

        // The description is now remembered with that COA for next time.
        $this->assertDatabaseHas('transaction_types', [
            'name' => 'Sysco Foods',
            'default_coa_id' => $coa->id,
        ]);
    }

    /** @test */
    public function reassigning_a_description_updates_the_remembered_coa(): void
    {
        $owner = User::factory()->create(['role' => UserRole::OWNER]);
        $store = Store::factory()->create(['created_by' => $owner->id]);
        $oldCoa = ChartOfAccount::create(['account_code' => '6300', 'account_name' => 'Supplies', 'account_type' => 'Expense', 'is_active' => true]);
        $newCoa = ChartOfAccount::create(['account_code' => '6310', 'account_name' => 'Cleaning', 'account_type' => 'Expense', 'is_active' => true]);

        // Description already remembered against the old COA.
        TransactionType::create(['name' => 'Sysco Foods', 'default_coa_id' => $oldCoa->id]);

        $this->actingAs($owner)->post('/daily-reports', $this->reportPayload($store->id, [
            ['transaction_id' => 1, 'company' => 'Sysco Foods', 'transaction_type' => $newCoa->id, 'amount' => 80],
        ]))->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('transaction_types', ['name' => 'Sysco Foods', 'default_coa_id' => $newCoa->id]);
        // Not duplicated — still one "Sysco Foods".
        $this->assertSame(1, TransactionType::whereRaw('LOWER(name) = ?', ['sysco foods'])->count());
    }
}
