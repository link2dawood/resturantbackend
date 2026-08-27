<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4: categorization rules are learned PER CLIENT (owner), not globally.
 *
 * owner_id scopes a rule to the business that learned it. Existing rows keep
 * owner_id = NULL and are treated as global fallbacks (so nothing breaks); new
 * rules are written with the owning business. is_active lets the admin rules
 * view deactivate a rule without deleting its learned history.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaction_mapping_rules', function (Blueprint $table) {
            if (! Schema::hasColumn('transaction_mapping_rules', 'owner_id')) {
                $table->foreignId('owner_id')->nullable()->after('id')
                    ->constrained('users')->nullOnDelete();
                $table->index('owner_id');
            }
            if (! Schema::hasColumn('transaction_mapping_rules', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('confidence_score');
                $table->index('is_active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transaction_mapping_rules', function (Blueprint $table) {
            if (Schema::hasColumn('transaction_mapping_rules', 'owner_id')) {
                $table->dropConstrainedForeignId('owner_id');
            }
            if (Schema::hasColumn('transaction_mapping_rules', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};
