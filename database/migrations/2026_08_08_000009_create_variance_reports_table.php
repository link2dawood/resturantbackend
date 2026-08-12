<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5: a snapshot header for a store's weekly variance report. The computed
 * per-item lines live in variance_report_lines.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('variance_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->date('week_start_date');
            $table->string('status', 20)->default('generated');
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'week_start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variance_reports');
    }
};
