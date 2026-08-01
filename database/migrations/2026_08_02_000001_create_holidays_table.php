<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Holidays are managed by the admin (self-service) instead of the static
 * config/holidays.php list. This creates the table and seeds it with the
 * existing defaults so nothing is lost.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed from the existing default list (idempotent-ish: table is new here).
        $now = now();
        $order = 0;
        $rows = [];
        foreach (config('holidays', []) as $name) {
            $rows[] = [
                'name' => $name,
                'sort_order' => $order++,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        if ($rows) {
            DB::table('holidays')->insert($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
