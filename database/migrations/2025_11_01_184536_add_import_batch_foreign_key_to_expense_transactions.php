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
        Schema::table('expense_transactions', function (Blueprint $table) {
            // Drop the old varchar column if it exists
            if (Schema::hasColumn('expense_transactions', 'import_batch_id')) {
                $table->dropColumn('import_batch_id');
            }
            
            // Add new foreign key column
            $table->foreignId('import_batch_id')->nullable()->after('duplicate_check_hash')->constrained('import_batches')->onDelete('set null');
            $table->index('import_batch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expense_transactions', function (Blueprint $table) {
            $table->dropForeign(['import_batch_id']);
            $table->dropColumn('import_batch_id');
            // Optionally restore varchar if needed
        });
    }
};
