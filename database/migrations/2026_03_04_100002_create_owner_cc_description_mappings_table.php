<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Learned mapping: normalized description pattern -> transaction type.
     * When owner assigns a type to a line, we save it here; next import we auto-apply.
     */
    public function up(): void
    {
        Schema::create('owner_cc_description_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('description_pattern', 255)->index();
            $table->foreignId('transaction_type_id')->constrained('transaction_types')->onDelete('cascade');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->unsignedInteger('times_matched')->default(0);
            $table->timestamps();

            $table->unique('description_pattern');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_cc_description_mappings');
    }
};
