<?php

namespace Database\Factories;

use App\Models\BankTransaction;
use App\Models\BankAccount;
use App\Models\ExpenseTransaction;
use App\Models\DailyReport;
use App\Models\ImportBatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BankTransaction>
 */
class BankTransactionFactory extends Factory
{
    protected $model = BankTransaction::class;

    public function definition(): array
    {
        return [
            'bank_account_id' => BankAccount::factory(),
            'transaction_date' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'post_date' => fake()->boolean(70) ? fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d') : null,
            'description' => fake()->sentence(),
            'transaction_type' => fake()->randomElement(['debit', 'credit']),
            'amount' => fake()->randomFloat(2, 1, 5000),
            'balance' => fake()->optional()->randomFloat(2, 0, 50000),
            'reference_number' => fake()->optional()->numerify('####'),
            'matched_expense_id' => null,
            'matched_revenue_id' => null,
            'reconciliation_status' => fake()->randomElement(['unmatched', 'matched', 'reviewed', 'exception']),
            'reconciliation_notes' => fake()->optional()->sentence(),
            'import_batch_id' => null,
            'duplicate_check_hash' => fake()->sha256(),
        ];
    }
}
