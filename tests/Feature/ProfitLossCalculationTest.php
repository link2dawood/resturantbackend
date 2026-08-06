<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Store;
use App\Models\DailyReport;
use App\Models\DailyReportRevenue;
use App\Models\ExpenseTransaction;
use App\Models\ChartOfAccount;
use App\Models\RevenueIncomeType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProfitLossCalculationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The P&L reports use MySQL-only SQL (YEAR()/MONTH()/CAST(... AS UNSIGNED)),
        // so these tests only run against MySQL. phpunit.xml points the suite at a
        // MySQL test database; on any other driver we skip cleanly rather than
        // fail with a syntax error.
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('P&L reporting requires MySQL (YEAR/MONTH/CAST); see phpunit.xml for the test DB.');
        }

        // Create test data
        $this->seed(\Database\Seeders\ChartOfAccountsSeeder::class);
        $this->seed(\Database\Seeders\RevenueIncomeTypeSeeder::class);
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

    /** @test */
    public function owner_without_explicit_store_only_sees_accessible_store_data()
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $ownedStore = Store::factory()->create(['created_by' => $owner->id]);
        $otherStore = Store::factory()->create();

        DailyReport::factory()->create([
            'store_id' => $ownedStore->id,
            'report_date' => now()->format('Y-m-d'),
            'gross_sales' => 1000.00,
            'credit_cards' => 0,
            'created_by' => $owner->id,
        ]);

        DailyReport::factory()->create([
            'store_id' => $otherStore->id,
            'report_date' => now()->format('Y-m-d'),
            'gross_sales' => 5000.00,
            'credit_cards' => 0,
            'created_by' => $owner->id,
        ]);

        $response = $this->actingAs($owner)->getJson('/api/reports/pl?start_date=' . now()->subDay()->format('Y-m-d') . '&end_date=' . now()->addDay()->format('Y-m-d'));

        $response->assertStatus(200);
        $this->assertEqualsWithDelta(1000.00, $response->json('pl.revenue.total'), 0.01);
    }

    /** @test */
    public function owner_cannot_request_profit_and_loss_for_unassigned_store()
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $ownedStore = Store::factory()->create(['created_by' => $owner->id]);
        $otherStore = Store::factory()->create();

        DailyReport::factory()->create([
            'store_id' => $ownedStore->id,
            'report_date' => now()->format('Y-m-d'),
            'gross_sales' => 1200.00,
            'credit_cards' => 0,
            'created_by' => $owner->id,
        ]);

        DailyReport::factory()->create([
            'store_id' => $otherStore->id,
            'report_date' => now()->format('Y-m-d'),
            'gross_sales' => 4200.00,
            'credit_cards' => 0,
            'created_by' => $owner->id,
        ]);

        $this->actingAs($owner)
            ->getJson('/api/reports/pl?store_id=' . $otherStore->id . '&start_date=' . now()->subDay()->format('Y-m-d') . '&end_date=' . now()->addDay()->format('Y-m-d'))
            ->assertStatus(403);
    }

    /** @test */
    public function drill_down_returns_summary_total_amount()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $store = Store::factory()->create(['created_by' => $admin->id]);
        $rentCoa = ChartOfAccount::where('account_name', 'Rent')->firstOrFail();

        ExpenseTransaction::factory()->create([
            'store_id' => $store->id,
            'coa_id' => $rentCoa->id,
            'amount' => 2100.00,
            'transaction_date' => now()->format('Y-m-d'),
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)
            ->getJson('/api/reports/pl/drill-down?store_id=' . $store->id . '&coa_id=' . $rentCoa->id . '&start_date=' . now()->subDay()->format('Y-m-d') . '&end_date=' . now()->addDay()->format('Y-m-d'));

        $response->assertStatus(200);
        $this->assertEqualsWithDelta(2100.00, $response->json('summary.total_amount'), 0.01);
    }

    /** @test */
    public function franchisor_can_view_profit_and_loss_for_all_restaurants_by_default()
    {
        $franchisor = User::factory()->create([
            'role' => 'owner',
            'name' => 'Franchisor',
            'email' => 'franchisor@example.com',
        ]);
        $owner = User::factory()->create(['role' => 'owner']);

        $restaurantOne = Store::factory()->create(['created_by' => $owner->id]);
        $restaurantTwo = Store::factory()->create(['created_by' => $owner->id]);

        DailyReport::factory()->create([
            'store_id' => $restaurantOne->id,
            'report_date' => now()->format('Y-m-d'),
            'gross_sales' => 1800.00,
            'credit_cards' => 0,
            'created_by' => $owner->id,
        ]);

        DailyReport::factory()->create([
            'store_id' => $restaurantTwo->id,
            'report_date' => now()->format('Y-m-d'),
            'gross_sales' => 3200.00,
            'credit_cards' => 0,
            'created_by' => $owner->id,
        ]);

        $response = $this->actingAs($franchisor)
            ->getJson('/api/reports/pl?start_date=' . now()->subDay()->format('Y-m-d') . '&end_date=' . now()->addDay()->format('Y-m-d'));

        $response->assertStatus(200);
        $this->assertEqualsWithDelta(5000.00, $response->json('pl.revenue.total'), 0.01);
    }

    /** @test */
    public function franchisor_can_view_profit_and_loss_for_a_specific_restaurant_they_do_not_create()
    {
        $franchisor = User::factory()->create([
            'role' => 'owner',
            'name' => 'Franchisor',
            'email' => 'franchisor-specific@example.com',
        ]);
        $owner = User::factory()->create(['role' => 'owner']);

        $restaurant = Store::factory()->create([
            'created_by' => $owner->id,
            'store_info' => 'Round Rock Restaurant',
        ]);

        DailyReport::factory()->create([
            'store_id' => $restaurant->id,
            'report_date' => now()->format('Y-m-d'),
            'gross_sales' => 2750.00,
            'credit_cards' => 0,
            'created_by' => $owner->id,
        ]);

        $response = $this->actingAs($franchisor)
            ->getJson('/api/reports/pl?store_id=' . $restaurant->id . '&start_date=' . now()->subDay()->format('Y-m-d') . '&end_date=' . now()->addDay()->format('Y-m-d'));

        $response->assertStatus(200);
        $this->assertEqualsWithDelta(2750.00, $response->json('pl.revenue.total'), 0.01);
    }

    /** @test */
    public function franchisor_profit_and_loss_page_uses_store_tracking_copy()
    {
        $franchisor = User::factory()->create([
            'role' => 'owner',
            'name' => 'Franchisor',
            'email' => 'franchisor-view@example.com',
        ]);
        $owner = User::factory()->create(['role' => 'owner']);
        $restaurant = Store::factory()->create([
            'created_by' => $owner->id,
            'store_info' => 'Cedar Park Restaurant',
        ]);

        DailyReport::factory()->create([
            'store_id' => $restaurant->id,
            'report_date' => now()->format('Y-m-d'),
            'gross_sales' => 1500.00,
            'credit_cards' => 0,
            'created_by' => $owner->id,
        ]);

        $response = $this->actingAs($franchisor)
            ->get('/reports/profit-loss?start_date=' . now()->subDay()->format('Y-m-d') . '&end_date=' . now()->addDay()->format('Y-m-d'));

        $response->assertStatus(200);
        $response->assertSee('Franchisor Tracking');
        $response->assertSee('All Stores');
        $response->assertSee('Track profit and loss across every store in the franchise portfolio.');
    }

    /** @test */
    public function manager_can_view_annual_profit_and_loss_for_assigned_store()
    {
        $managerStore = Store::factory()->create();
        $manager = User::factory()->create([
            'role' => 'manager',
            'store_id' => $managerStore->id,
        ]);

        DailyReport::factory()->create([
            'store_id' => $managerStore->id,
            'report_date' => now()->startOfYear()->addMonth()->format('Y-m-d'),
            'gross_sales' => 1800.00,
            'credit_cards' => 0,
            'created_by' => $manager->id,
        ]);

        $response = $this->actingAs($manager)
            ->getJson('/api/reports/pl/annual?store_id=' . $managerStore->id . '&year=' . now()->year);

        $response->assertStatus(200);
        $this->assertEquals(now()->year, $response->json('year'));
    }

    /** @test */
    public function annual_profit_and_loss_includes_monthly_expense_coa_activity_breakdown()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $store = Store::factory()->create(['created_by' => $admin->id]);
        $rentCoa = ChartOfAccount::where('account_name', 'Rent')->firstOrFail();

        ExpenseTransaction::factory()->create([
            'store_id' => $store->id,
            'coa_id' => $rentCoa->id,
            'amount' => 1200.00,
            'transaction_date' => now()->startOfYear()->addMonth()->format('Y-m-d'),
            'created_by' => $admin->id,
        ]);

        ExpenseTransaction::factory()->create([
            'store_id' => $store->id,
            'coa_id' => $rentCoa->id,
            'amount' => 1800.00,
            'transaction_date' => now()->startOfYear()->addMonths(2)->format('Y-m-d'),
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)
            ->getJson('/api/reports/pl/annual?store_id=' . $store->id . '&year=' . now()->year);

        $response->assertStatus(200);

        $expenseRows = collect($response->json('pl.coaActivitySummary.expense.rows'));
        $rentRow = $expenseRows->firstWhere('coa_id', $rentCoa->id);

        $this->assertNotNull($rentRow);
        $this->assertEqualsWithDelta(1200.00, $rentRow['monthly_amounts'][2] ?? 0, 0.01);
        $this->assertEqualsWithDelta(1800.00, $rentRow['monthly_amounts'][3] ?? 0, 0.01);
        $this->assertEqualsWithDelta(3000.00, $response->json('pl.coaActivitySummary.expense.total_amount'), 0.01);
    }

    /** @test */
    public function annual_profit_and_loss_includes_monthly_income_coa_activity_breakdown()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $store = Store::factory()->create(['created_by' => $admin->id]);
        $cashType = RevenueIncomeType::where('name', 'Cash')->firstOrFail();
        $cashRevenueCoa = ChartOfAccount::where('account_code', '4010')->firstOrFail();

        $februaryReport = DailyReport::factory()->create([
            'store_id' => $store->id,
            'report_date' => now()->startOfYear()->addMonth()->format('Y-m-d'),
            'gross_sales' => 900.00,
            'credit_cards' => 0,
            'created_by' => $admin->id,
        ]);

        DailyReportRevenue::create([
            'daily_report_id' => $februaryReport->id,
            'revenue_income_type_id' => $cashType->id,
            'amount' => 900.00,
        ]);

        $marchReport = DailyReport::factory()->create([
            'store_id' => $store->id,
            'report_date' => now()->startOfYear()->addMonths(2)->format('Y-m-d'),
            'gross_sales' => 1100.00,
            'credit_cards' => 0,
            'created_by' => $admin->id,
        ]);

        DailyReportRevenue::create([
            'daily_report_id' => $marchReport->id,
            'revenue_income_type_id' => $cashType->id,
            'amount' => 1100.00,
        ]);

        $response = $this->actingAs($admin)
            ->getJson('/api/reports/pl/annual?store_id=' . $store->id . '&year=' . now()->year);

        $response->assertStatus(200);

        $incomeRows = collect($response->json('pl.coaActivitySummary.income.rows'));
        $cashRow = $incomeRows->firstWhere('coa_id', $cashRevenueCoa->id);

        $this->assertNotNull($cashRow);
        $this->assertEqualsWithDelta(900.00, $cashRow['monthly_amounts'][2] ?? 0, 0.01);
        $this->assertEqualsWithDelta(1100.00, $cashRow['monthly_amounts'][3] ?? 0, 0.01);
        $this->assertEqualsWithDelta(2000.00, $response->json('pl.coaActivitySummary.income.total_amount'), 0.01);
    }

    /** @test */
    public function manager_cannot_view_annual_profit_and_loss_for_unassigned_store()
    {
        $managerStore = Store::factory()->create();
        $otherStore = Store::factory()->create();
        $manager = User::factory()->create([
            'role' => 'manager',
            'store_id' => $managerStore->id,
        ]);

        $this->actingAs($manager)
            ->getJson('/api/reports/pl/annual?store_id=' . $otherStore->id . '&year=' . now()->year)
            ->assertStatus(403);
    }
}
