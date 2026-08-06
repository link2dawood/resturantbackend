<?php

namespace Tests\Feature;

use App\Models\DailyReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Spec: every transaction must be store-attributed. A daily report cannot be
 * created without a store (enforced at the app layer; the column is also
 * NOT NULL at the DB layer as of the store_id_required migration).
 */
class DailyReportStoreAttributionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_daily_report_cannot_be_created_without_a_store(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post('/daily-reports', [
            // store_id intentionally omitted
            'report_date' => now()->format('Y-m-d'),
            'projected_sales' => 1000.00,
            'gross_sales' => 1200.00,
            'total_paid_outs' => 0.00,
            'total_customers' => 10,
            'credit_cards' => 0.00,
            'actual_deposit' => 1200.00,
        ]);

        $response->assertSessionHasErrors('store_id');
        $this->assertSame(0, DailyReport::count());
    }
}
