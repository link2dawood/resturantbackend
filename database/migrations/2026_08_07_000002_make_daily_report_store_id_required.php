<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Spec: "Every transaction must be timestamped and store-attributed."
 *
 * store_id is already required when creating a daily report (controller
 * validation + daily_report_access middleware), but the column was nullable —
 * so a stray null-store row was theoretically possible, and such a row is
 * globally visible under the tenant scope. This tightens the schema to match
 * the application rule.
 *
 * Data safety: if any existing report has a null store_id, we abort with
 * guidance instead of forcing the change (which would fail anyway) or silently
 * skipping (which would leave the gap unnoticed). Assign those rows a store,
 * then re-run.
 */
return new class extends Migration
{
    public function up(): void
    {
        $nullCount = DB::table('daily_reports')->whereNull('store_id')->count();

        if ($nullCount > 0) {
            throw new \RuntimeException(
                "Cannot make daily_reports.store_id required: {$nullCount} report(s) have no store_id. ".
                'Assign each of those reports a store, then re-run this migration.'
            );
        }

        Schema::table('daily_reports', function (Blueprint $table) {
            $table->unsignedBigInteger('store_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('daily_reports', function (Blueprint $table) {
            $table->unsignedBigInteger('store_id')->nullable()->change();
        });
    }
};
