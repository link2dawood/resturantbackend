<?php

namespace Database\Factories;

use App\Models\DailyReport;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DailyReport>
 */
class DailyReportFactory extends Factory
{
    protected $model = DailyReport::class;

    public function definition(): array
    {
        $grossSales = fake()->randomFloat(2, 5000, 15000);
        $amountCancels = fake()->randomFloat(2, 0, 100);
        $amountVoids = fake()->randomFloat(2, 0, 50);
        $couponsReceived = fake()->randomFloat(2, 0, 200);
        $adjustments = fake()->randomFloat(2, 0, 100);
        $tax = fake()->randomFloat(2, 0, 500);
        
        $netSales = $grossSales - $amountCancels - $amountVoids - $couponsReceived;
        $totalCustomers = fake()->numberBetween(100, 500);
        $averageTicket = $totalCustomers > 0 ? $netSales / $totalCustomers : 0;
        
        return [
            'projected_sales' => fake()->randomFloat(2, 5000, 15000),
            'amount_of_cancels' => $amountCancels,
            'amount_of_voids' => $amountVoids,
            'number_of_no_sales' => fake()->numberBetween(0, 5),
            'total_coupons' => fake()->numberBetween(0, 200),
            'gross_sales' => $grossSales,
            'coupons_received' => $couponsReceived,
            'adjustments_overrings' => $adjustments,
            'total_customers' => $totalCustomers,
            'net_sales' => $netSales,
            'tax' => $tax,
            'average_ticket' => $averageTicket,
            'sales' => $netSales - $tax,
            'total_paid_outs' => fake()->randomFloat(2, 0, 500),
            'credit_cards' => fake()->randomFloat(2, 1000, 8000),
            'cash_to_account' => fake()->randomFloat(2, 100, 2000),
            'actual_deposit' => fake()->randomFloat(2, 2000, 10000),
            'short' => fake()->randomFloat(2, 0, 20),
            'over' => fake()->randomFloat(2, 0, 20),
            'report_date' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'page_number' => 1,
            'weather' => fake()->randomElement(['Sunny', 'Rainy', 'Cloudy', 'Windy', null]),
            'holiday_event' => null,
            'store_id' => Store::factory(),
            'created_by' => User::factory(),
        ];
    }
}
