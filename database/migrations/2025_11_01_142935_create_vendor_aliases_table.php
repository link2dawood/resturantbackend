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
        Schema::create('vendor_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->onDelete('cascade');
            $table->string('alias', 100);
            $table->enum('source', ['bank', 'credit_card', 'manual']);
            $table->timestamps();
            
            // Indexes
            $table->index('alias');
            $table->index('source');
            
            // Unique constraint
            $table->unique(['alias', 'source']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_aliases');
    }
};
