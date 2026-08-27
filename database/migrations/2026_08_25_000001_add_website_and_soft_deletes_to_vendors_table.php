<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5 Part 1 - brings the existing `vendors` table up to the ordering spec.
 *
 * `vendors` already carries name, contact_name, contact_email, contact_phone,
 * address, notes and is_active (see 2025_11_01_142901), and already indexes
 * vendor_name. Missing pieces are the ordering website and soft deletes, so the
 * client can hide a vendor without losing the expense history attached to it.
 *
 * NOTE on "name unique": the spec asks for a unique name, but a database-level
 * UNIQUE index is not safe on this table today, for two reasons:
 *   1. `vendors:dedupe` exists because duplicate names are a real, historical
 *      state of this data (bank and third-party imports created them), and
 *      DedupeVendorsCommandTest creates duplicates on purpose to test the fix.
 *   2. Combined with the soft deletes added here, a hidden vendor would keep its
 *      name reserved, so DailyReportObserver's "create the Square vendor if it is
 *      missing" path would start throwing a unique violation on every daily
 *      report save instead of finding the trashed row.
 * Uniqueness stays enforced where it already is: all three creation paths
 * (Api\VendorController, DailyReportObserver, Api\ThirdPartyImportController)
 * look the name up before inserting. To enforce it in the database instead, run
 * `php artisan vendors:dedupe --apply` first, teach those lookups to use
 * withTrashed(), then add `$table->unique('vendor_name')` in a new migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            if (! Schema::hasColumn('vendors', 'website')) {
                $table->string('website', 255)->nullable()->after('contact_phone');
            }

            if (! Schema::hasColumn('vendors', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            if (Schema::hasColumn('vendors', 'deleted_at')) {
                $table->dropSoftDeletes();
            }

            if (Schema::hasColumn('vendors', 'website')) {
                $table->dropColumn('website');
            }
        });
    }
};
