<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Spec constraint: "All monetary values stored as decimal(12,2)."
 *
 * Most money columns were created as decimal(10,2). Widening 10,2 -> 12,2 is a
 * lossless change (every existing value fits), so this is safe to run on live
 * data. Nullability and defaults are preserved exactly as originally defined.
 */
return new class extends Migration
{
    /** table => money columns declared NOT NULL DEFAULT 0. */
    private array $notNullDefaultZero = [
        'daily_reports' => [
            'projected_sales', 'amount_of_cancels', 'amount_of_voids', 'gross_sales',
            'coupons_received', 'adjustments_overrings', 'adjustments_cash', 'adjustments_credit_card',
            'net_sales', 'tax', 'average_ticket', 'sales', 'total_paid_outs', 'credit_cards',
            'credit_card_tips', 'tips', 'cash_to_account', 'actual_deposit', 'short', 'over',
        ],
        'daily_report_transactions' => ['amount'],
        'bank_accounts' => ['opening_balance', 'current_balance'],
        'third_party_statements' => [
            'gross_sales', 'marketing_fees', 'delivery_fees', 'processing_fees',
            'adjustments', 'net_deposit', 'sales_tax_collected',
        ],
    ];

    /** table => money columns declared NOT NULL with no default. */
    private array $notNull = [
        'expense_transactions' => ['amount'],
        'daily_report_revenues' => ['amount'],
        'bank_transactions' => ['amount'],
    ];

    /** table => nullable money columns. */
    private array $nullable = [
        'bank_transactions' => ['balance'],
    ];

    public function up(): void
    {
        $this->apply(12);
    }

    public function down(): void
    {
        // Best-effort revert to the original precision.
        $this->apply(10);
    }

    private function apply(int $precision): void
    {
        foreach ($this->notNullDefaultZero as $table => $cols) {
            Schema::table($table, function (Blueprint $t) use ($table, $cols, $precision) {
                foreach ($cols as $col) {
                    if (Schema::hasColumn($table, $col)) {
                        $t->decimal($col, $precision, 2)->default(0)->change();
                    }
                }
            });
        }

        foreach ($this->notNull as $table => $cols) {
            Schema::table($table, function (Blueprint $t) use ($table, $cols, $precision) {
                foreach ($cols as $col) {
                    if (Schema::hasColumn($table, $col)) {
                        $t->decimal($col, $precision, 2)->change();
                    }
                }
            });
        }

        foreach ($this->nullable as $table => $cols) {
            Schema::table($table, function (Blueprint $t) use ($table, $cols, $precision) {
                foreach ($cols as $col) {
                    if (Schema::hasColumn($table, $col)) {
                        $t->decimal($col, $precision, 2)->nullable()->change();
                    }
                }
            });
        }
    }
};
