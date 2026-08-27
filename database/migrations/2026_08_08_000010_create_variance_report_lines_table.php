<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5: the per-item computed variance snapshot. Mirrors the VarianceLine the
 * service produces (see docs variance-formula.md). All quantities in the item's
 * base unit; variance/variance_pct/severity are null when the line is incomplete
 * (missing starting or ending count).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('variance_report_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variance_report_id')->constrained('variance_reports')->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->decimal('starting_stock', 14, 4)->default(0);
            $table->decimal('ordered_qty', 14, 4)->default(0);
            $table->decimal('total_available', 14, 4)->default(0);
            $table->decimal('theoretical_usage', 14, 4)->default(0);
            $table->decimal('theoretical_ending', 14, 4)->default(0);
            $table->decimal('actual_ending', 14, 4)->nullable();
            $table->decimal('variance', 14, 4)->nullable();
            $table->decimal('variance_pct', 8, 4)->nullable();
            $table->string('severity', 10)->nullable(); // green|yellow|red
            $table->string('base_unit', 20);
            $table->boolean('is_incomplete')->default(false);
            $table->timestamps();

            $table->index('variance_report_id');
            $table->index('inventory_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variance_report_lines');
    }
};
