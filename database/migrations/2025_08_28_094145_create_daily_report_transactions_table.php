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
        Schema::create('daily_report_transactions', function (Blueprint $table) {
            $table->id();
            
            // Relationship to daily report
            $table->foreignId('daily_report_id')->nullable()->constrained('daily_reports')->onDelete('cascade');
            $table->foreignId('transaction_type_id')->nullable()->constrained('transaction_types')->onDelete('cascade');
            $table->string('company', 100)->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_report_transactions');
    }
};
