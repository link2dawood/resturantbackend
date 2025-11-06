<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Store;
use App\Models\DailyReport;
use App\Models\ExpenseTransaction;
use App\Models\ThirdPartyStatement;
use App\Models\ChartOfAccount;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ProfitLossCalculationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->seed(\Database\Seeders\ChartOfAccountsSeeder::class);
    }

    /** @test */
    public function it_calculates_complete_p_and_l_statement()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $store = Store::factory()->create(['created_by' => $user->id]);
        
        // Create revenue
        $foodCoa = ChartOfAccount::where('account_name', 'Revenue - Food Sales')->first();
        $beverageCoa = ChartOfAccount::where('account_name', 'Revenue - Beverage Sales')->first();
        
        // Create COGS
        $foodPurchasesCoa = ChartOfAccount::where('account_name', 'COGS - Food Purchases')->first();
        $packagingCoa = ChartOfAccount::where('account_name', 'COGS - Packaging Supplies')->first();
        
        // Create expenses
        $payrollCoa = ChartOfAccount::where('account_name', 'Payroll')->first();
        $rentCoa = ChartOfAccount::where('account_name', 'Rent')->first();
        
        // Create daily reports for revenue (set credit_cards to 0 to avoid merchant fee transactions)
        DailyReport::factory()->create([
            'store_id' => $store->id,
            'report_date' => now()->subDays(1)->format('Y-m-d'),
            'gross_sales' => 10000.00,
            'credit_cards' => 0, // No credit cards to avoid merchant fee transactions
            'created_by' => $user->id,
        ]);
        
        DailyReport::factory()->create([
            'store_id' => $store->id,
            'report_date' => now()->format('Y-m-d'),
            'gross_sales' => 15000.00,
            'credit_cards' => 0, // No credit cards to avoid merchant fee transactions
            'created_by' => $user->id,
        ]);
        
        // Create COGS expenses
        ExpenseTransaction::factory()->create([
            'store_id' => $store->id,
            'coa_id' => $foodPurchasesCoa->id,
            'amount' => 3000.00,
            'transaction_date' => now()->subDays(1)->format('Y-m-d'),
            'created_by' => $user->id,
        ]);
        
        ExpenseTransaction::factory()->create([
            'store_id' => $store->id,
            'coa_id' => $packagingCoa->id,
            'amount' => 500.00,
            'transaction_date' => now()->format('Y-m-d'),
            'created_by' => $user->id,
        ]);
        
        // Create operating expenses
        ExpenseTransaction::factory()->create([
            'store_id' => $store->id,
            'coa_id' => $payrollCoa->id,
            'amount' => 5000.00,
            'transaction_date' => now()->subDays(1)->format('Y-m-d'),
            'created_by' => $user->id,
        ]);
        
        ExpenseTransaction::factory()->create([
            'store_id' => $store->id,
            'coa_id' => $rentCoa->id,
            'amount' => 2000.00,
            'transaction_date' => now()->format('Y-m-d'),
            'created_by' => $user->id,
        ]);
        
        // Use a wider date range to ensure both reports are included
        $startDate = now()->subDays(2)->format('Y-m-d');
        $endDate = now()->addDay()->format('Y-m-d');
        
        // Make request to P&L API
        $response = $this->actingAs($user)
            ->getJson('/api/reports/pl?store_id=' . $store->id . '&start_date=' . $startDate . '&end_date=' . $endDate);
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'period',
                'pl' => [
                    'revenue' => ['items', 'total'],
                    'cogs' => ['items', 'total'],
                    'gross_profit',
                    'gross_margin',
                    'operating_expenses' => ['items', 'total'],
                    'net_profit',
                    'net_margin'
                ]
            ]);
        
        $data = $response->json();
        
        // Verify calculations (with tolerance for floating point)
        $this->assertEqualsWithDelta(25000.00, $data['pl']['revenue']['total'], 0.01, 'Total revenue should be 25,000');
        $this->assertEqualsWithDelta(3500.00, $data['pl']['cogs']['total'], 0.01, 'Total COGS should be 3,500');
        $this->assertEqualsWithDelta(21500.00, $data['pl']['gross_profit'], 0.01, 'Gross profit should be 21,500');
        $this->assertEqualsWithDelta(86.0, $data['pl']['gross_margin'], 0.1, 'Gross margin should be 86%');
        $this->assertEqualsWithDelta(7000.00, $data['pl']['operating_expenses']['total'], 0.01, 'Operating expenses should be 7,000');
        $this->assertEqualsWithDelta(14500.00, $data['pl']['net_profit'], 0.01, 'Net profit should be 14,500');
        $this->assertEqualsWithDelta(58.0, $data['pl']['net_margin'], 0.1, 'Net margin should be 58%');
    }

    /** @test */
    public function it_filters_by_store_access()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'owner']);
        
        $store1 = Store::factory()->create(['created_by' => $owner->id]);
        $store2 = Store::factory()->create(['created_by' => $owner->id]);
        $store3 = Store::factory()->create();
        
        // Manager should not see store3 data
        $response = $this->actingAs($owner)
            ->getJson('/api/reports/pl');
        
        $response->assertStatus(200);
    }

    /** @test */
    public function it_generates_p_and_l_summary()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $store = Store::factory()->create(['created_by' => $user->id]);
        
        $response = $this->actingAs($user)
            ->getJson('/api/reports/pl/summary?store_id=' . $store->id);
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'revenue',
                'cogs',
                'gross_profit',
                'operating_expenses',
                'net_profit',
                'gross_margin',
                'net_margin'
            ]);
    }
}
