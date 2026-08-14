<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5.6 — each inventory item can have a preferred (default) vendor, used to
 * label items on the combined weekly order list and to group them into per-vendor
 * orders. Nullable; the cheapest-vendor logic (module 5.7) may update it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            if (! Schema::hasColumn('inventory_items', 'preferred_vendor_id')) {
                $table->foreignId('preferred_vendor_id')->nullable()->after('store_id')
                    ->constrained('vendors')->nullOnDelete();
                $table->index('preferred_vendor_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_items', 'preferred_vendor_id')) {
                $table->dropConstrainedForeignId('preferred_vendor_id');
            }
        });
    }
};
