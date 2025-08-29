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
            
            // Header Info
            $table->string('restaurant_name', 100)->default("Phil's Philly Steaks - Hulen Mall");
            $table->string('address')->nullable();
            $table->string('phone', 20)->nullable();
            $table->date('report_date');
            $table->integer('page_number')->default(1);
            
            // Environmental Data
            $table->string('weather', 50)->nullable();
            $table->string('holiday_event', 100)->nullable();
            
            // Sales Data
            $table->decimal('projected_sales', 10, 2)->default(0);
            $table->decimal('gross_sales', 10, 2)->default(0);
            $table->decimal('amount_of_cancels', 10, 2)->default(0);
            $table->decimal('amount_of_voids', 10, 2)->default(0);
            $table->integer('number_of_no_sales')->default(0);
            
            // Coupons & Adjustments
            $table->integer('total_coupons')->default(0);
            $table->decimal('coupons_received', 10, 2)->default(0);
            $table->decimal('adjustments_overrings', 10, 2)->default(0);
            
            // Customer Data
            $table->integer('total_customers')->default(0);
            
            // Payment Data
            $table->decimal('credit_cards', 10, 2)->default(0);
            $table->decimal('actual_deposit', 10, 2)->default(0);
            
            // Store relationship
            $table->foreignId('store_id')->nullable()->constrained('stores')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            
            $table->timestamps();
            
            // Unique constraint: one report per store per date
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
