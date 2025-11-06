<?php

namespace Database\Factories;

use App\Models\BankAccount;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BankAccount>
 */
class BankAccountFactory extends Factory
{
    protected $model = BankAccount::class;

    public function definition(): array
    {
        return [
            'bank_name' => fake()->randomElement(['Chase', 'Bank of America', 'Wells Fargo', 'Citibank', 'TD Bank']),
            'account_number_last_four' => fake()->numerify('####'),
            'account_type' => fake()->randomElement(['checking', 'savings', 'credit_card']),
            'store_id' => fake()->optional()->numberBetween(1, 10),
            'opening_balance' => fake()->randomFloat(2, 0, 50000),
            'current_balance' => fake()->randomFloat(2, 0, 50000),
            'last_reconciled_date' => fake()->boolean(70) ? fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d') : null,
            'is_active' => true,
        ];
    }
}
