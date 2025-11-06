<?php

namespace Database\Factories;

use App\Models\ExpenseTransaction;
use App\Models\Store;
use App\Models\Vendor;
use App\Models\ChartOfAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ExpenseTransaction>
 */
class ExpenseTransactionFactory extends Factory
{
    protected $model = ExpenseTransaction::class;

    public function definition(): array
    {
        return [
            'transaction_type' => fake()->randomElement(['cash', 'credit_card', 'bank_transfer', 'check']),
            'transaction_date' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'post_date' => fake()->boolean(70) ? fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d') : null,
            'store_id' => Store::factory(),
            'vendor_id' => null, // Will be set explicitly in tests if needed
            'vendor_name_raw' => fake()->company(),
            'coa_id' => null, // Will be set explicitly in tests if needed
            'amount' => fake()->randomFloat(2, 10, 1000),
            'description' => fake()->sentence(),
            'reference_number' => fake()->optional()->numerify('####'),
            'payment_method' => fake()->randomElement(['cash', 'credit_card', 'debit_card', 'check', 'eft', 'other']),
            'card_last_four' => fake()->optional()->numerify('####'),
            'receipt_url' => fake()->optional()->url(),
            'notes' => fake()->optional()->sentence(),
            'is_reconciled' => false,
            'reconciled_date' => null,
            'reconciled_by' => null,
            'needs_review' => false,
            'review_reason' => null,
            'duplicate_check_hash' => fake()->sha256(),
            'import_batch_id' => null,
            'daily_report_id' => null,
            'third_party_statement_id' => null,
            'created_by' => User::factory(),
        ];
    }
}
