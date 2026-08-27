<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5: a versioned recipe for a (menu_item, size_variant). Editing a recipe
 * creates a new version row and flips is_current, preserving history.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_item_id')->constrained('menu_items')->cascadeOnDelete();
            $table->string('size_variant', 20)->default('regular'); // mini|regular|large
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_current')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['menu_item_id', 'size_variant', 'is_current']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipes');
    }
};
