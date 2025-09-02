<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('daily_report_revenues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_report_id')->constrained('daily_reports')->onDelete('cascade');
            $table->foreignId('revenue_income_type_id')->constrained('revenue_income_types')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable(); // For check numbers, reference IDs, etc.
            $table->timestamps();

            $table->index(['daily_report_id', 'revenue_income_type_id'], 'daily_report_revenue_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_report_revenues');
    }
};
