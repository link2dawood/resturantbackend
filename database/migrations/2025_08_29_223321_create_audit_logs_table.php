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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('auditable_type'); // Model type (e.g., DailyReport)
            $table->unsignedBigInteger('auditable_id'); // Model ID
            $table->string('action'); // created, updated, deleted, etc.
            $table->json('old_values')->nullable(); // Previous values
            $table->json('new_values')->nullable(); // New values
            $table->unsignedBigInteger('user_id')->nullable(); // User who performed the action
            $table->string('user_name')->nullable(); // User name (for reference)
            $table->string('ip_address', 45)->nullable(); // IP address
            $table->string('user_agent')->nullable(); // User agent
            $table->json('additional_data')->nullable(); // Any additional context
            $table->timestamps();

            // Indexes for better performance
            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['user_id']);
            $table->index(['action']);
            $table->index(['created_at']);

            // Foreign key
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
