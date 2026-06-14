<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4 — Sales Projection Calendar.
 *
 * Per-tenant (per-store) daily sales projections. A projection can exist for a
 * future date that has no DailyReport yet; actuals come from DailyReport on the
 * same store+date. One projection per store per day.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_projections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->date('projection_date');
            $table->decimal('amount', 12, 2)->default(0);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['store_id', 'projection_date']);
        });

        // Backfill from projections already entered on filed daily reports so the
        // calendar is populated for existing data (one report per store/day, so no
        // unique conflicts). Done in PHP for cross-database portability.
        DB::table('daily_reports')
            ->select('store_id', 'report_date', 'projected_sales')
            ->where('projected_sales', '>', 0)
            ->whereNotNull('store_id')
            ->orderBy('id')
            ->chunk(500, function ($rows) {
                $now = now();
                $payload = $rows->map(fn ($r) => [
                    'store_id' => $r->store_id,
                    'projection_date' => $r->report_date,
                    'amount' => $r->projected_sales,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

                DB::table('sales_projections')->insertOrIgnore($payload);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_projections');
    }
};
