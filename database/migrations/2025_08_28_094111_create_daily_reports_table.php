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
        Schema::create('daily_reports', function (Blueprint $table) {
            $table->id();
            $table->decimal('projected_sales', 10, 2)->default(0);
            $table->decimal('amount_of_cancels', 10, 2)->default(0);
            $table->decimal('amount_of_voids', 10, 2)->default(0);
            $table->integer('number_of_no_sales')->default(0);
            $table->integer('total_coupons')->default(0);
            $table->decimal('gross_sales', 10, 2)->default(0);
            $table->decimal('coupons_received', 10, 2)->default(0);
            $table->decimal('adjustments_overrings', 10, 2)->default(0);
            $table->integer('total_customers')->default(0);
            $table->decimal('net_sales', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('average_ticket', 10, 2)->default(0);
            $table->decimal('sales', 10, 2)->default(0);
            $table->decimal('total_paid_outs', 10, 2)->default(0);
            $table->decimal('credit_cards', 10, 2)->default(0);
            $table->decimal('cash_to_account', 10, 2)->default(0);
            $table->decimal('actual_deposit', 10, 2)->default(0);
            $table->decimal('short', 10, 2)->default(0);
            $table->decimal('over', 10, 2)->default(0);
            $table->date('report_date')->nullable();
            $table->integer('page_number')->default(1);
            $table->string('weather', 50)->nullable();
            $table->string('holiday_event', 100)->nullable();
            $table->foreignId('store_id')->nullable()->constrained('stores')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['store_id', 'report_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_reports');
    }
};
