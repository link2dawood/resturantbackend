<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * De-brand the users.fanns_philly_email column.
 *
 * The spec forbids hardcoding restaurant-specific data; a column named after
 * one brand is exactly that. Rename (not drop) so existing values are preserved
 * while the schema becomes brand-neutral.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'fanns_philly_email') && ! Schema::hasColumn('users', 'franchise_email')) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('fanns_philly_email', 'franchise_email');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'franchise_email') && ! Schema::hasColumn('users', 'fanns_philly_email')) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('franchise_email', 'fanns_philly_email');
            });
        }
    }
};
