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
        Schema::create('pl_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->foreignId('store_id')->nullable()->constrained('stores')->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date');
            $table->json('pl_data'); // Store complete P&L structure as JSON
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            $table->index(['store_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pl_snapshots');
    }
};
