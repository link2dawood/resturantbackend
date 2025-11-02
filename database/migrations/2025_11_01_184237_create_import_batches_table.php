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
        Schema::create('import_batches', function (Blueprint $table) {
            $table->id();
            $table->enum('import_type', ['credit_card', 'bank_statement']);
            $table->string('file_name', 255);
            $table->string('file_hash', 64)->index();
            $table->foreignId('store_id')->nullable()->constrained('stores')->onDelete('cascade');
            $table->integer('transaction_count')->default(0);
            $table->integer('imported_count')->default(0);
            $table->integer('duplicate_count')->default(0);
            $table->integer('error_count')->default(0);
            $table->integer('needs_review_count')->default(0);
            $table->date('date_range_start')->nullable();
            $table->date('date_range_end')->nullable();
            $table->enum('status', ['processing', 'completed', 'failed'])->default('processing');
            $table->text('error_log')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->foreignId('imported_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();
            
            // Additional indexes
            $table->index('import_type');
            $table->index('status');
            $table->index('imported_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_batches');
    }
};
