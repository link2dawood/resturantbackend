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
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('vendor_name', 100);
            $table->string('vendor_identifier', 100)->nullable();
            $table->foreignId('default_coa_id')->nullable()->constrained('chart_of_accounts')->onDelete('set null');
            $table->enum('vendor_type', ['Food', 'Beverage', 'Supplies', 'Utilities', 'Services', 'Other']);
            $table->string('contact_name', 100)->nullable();
            $table->string('contact_email', 100)->nullable();
            $table->string('contact_phone', 50)->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            // Indexes
            $table->index('vendor_name');
            $table->index('vendor_identifier');
            $table->index('vendor_type');
            $table->index('is_active');
            
            // Unique constraint
            $table->unique('vendor_identifier');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
