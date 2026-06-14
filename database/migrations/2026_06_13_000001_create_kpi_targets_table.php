<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4 — KPI configuration.
 *
 * Per-tenant (per-store) user-defined KPI targets for the dashboard cost rings.
 * One row per store; NULL columns fall back to the config defaults in
 * config/dashboard.php.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->unique()->constrained('stores')->cascadeOnDelete();
            $table->decimal('food_cost_pct', 5, 2)->nullable();
            $table->decimal('payroll_pct', 5, 2)->nullable();
            $table->decimal('rent_pct', 5, 2)->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_targets');
    }
};
