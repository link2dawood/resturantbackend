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
            $table->foreignId('daily_report_id')->constrained('daily_reports')->onDelete('cascade');
            
            // Transaction details
            $table->integer('transaction_id');
            $table->string('company', 100);
            $table->enum('transaction_type', ['Food Cost', 'Rent', 'Accounting', 'Taxes', 'Other']);
            $table->decimal('amount', 10, 2);
            
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
