<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_reports', function (Blueprint $table) {
            // Credit Card split: `credit_cards` keeps its meaning (CARD SALES);
            // tips charged to cards are tracked separately (paid out in cash).
            if (! Schema::hasColumn('daily_reports', 'credit_card_tips')) {
                $table->decimal('credit_card_tips', 10, 2)->default(0)->after('credit_cards');
            }
            // Adjustment breakdown — all reduce net sales, alongside the existing
            // adjustments_overrings.
            if (! Schema::hasColumn('daily_reports', 'adjustments_cash')) {
                $table->decimal('adjustments_cash', 10, 2)->default(0)->after('adjustments_overrings');
            }
            if (! Schema::hasColumn('daily_reports', 'adjustments_credit_card')) {
                $table->decimal('adjustments_credit_card', 10, 2)->default(0)->after('adjustments_cash');
            }
            if (! Schema::hasColumn('daily_reports', 'tips')) {
                $table->decimal('tips', 10, 2)->default(0)->after('adjustments_credit_card');
            }
        });
    }

    public function down(): void
    {
        Schema::table('daily_reports', function (Blueprint $table) {
            foreach (['credit_card_tips', 'adjustments_cash', 'adjustments_credit_card', 'tips'] as $col) {
                if (Schema::hasColumn('daily_reports', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
