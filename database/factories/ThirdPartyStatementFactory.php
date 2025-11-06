<?php

namespace Database\Factories;

use App\Models\ThirdPartyStatement;
use App\Models\Store;
use App\Models\User;
use App\Models\ImportBatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ThirdPartyStatement>
 */
class ThirdPartyStatementFactory extends Factory
{
    protected $model = ThirdPartyStatement::class;

    public function definition(): array
    {
        $grossSales = fake()->randomFloat(2, 1000, 10000);
        $marketingFees = $grossSales * 0.15;
        $deliveryFees = $grossSales * 0.10;
        $processingFees = fake()->randomFloat(2, 10, 50);
        $totalFees = $marketingFees + $deliveryFees + $processingFees;
        $netDeposit = $grossSales - $totalFees;
        
        return [
            'platform' => fake()->randomElement(['grubhub', 'ubereats', 'doordash']),
            'store_id' => Store::factory(),
            'statement_date' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'statement_id' => fake()->bothify('STMT-####-####'),
            'gross_sales' => $grossSales,
            'marketing_fees' => $marketingFees,
            'delivery_fees' => $deliveryFees,
            'processing_fees' => $processingFees,
            'net_deposit' => $netDeposit,
            'sales_tax_collected' => fake()->randomFloat(2, 0, 500),
            'import_batch_id' => null,
            'file_name' => fake()->word() . '.pdf',
            'file_hash' => fake()->sha256(),
            'imported_by' => User::factory(),
        ];
    }
}




