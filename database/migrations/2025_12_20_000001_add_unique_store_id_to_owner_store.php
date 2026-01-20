<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration enforces that one store can have only one owner
     * by adding a unique constraint on store_id in the owner_store pivot table.
     */
    public function up(): void
    {
        // First, clean up any existing duplicate store assignments
        // Keep only the first owner for each store
        $duplicates = \DB::table('owner_store')
            ->select('store_id', \DB::raw('COUNT(*) as count'))
            ->groupBy('store_id')
            ->having('count', '>', 1)
            ->get();

        foreach ($duplicates as $duplicate) {
            // Keep the first owner (oldest created_at), remove others
            $firstOwner = \DB::table('owner_store')
                ->where('store_id', $duplicate->store_id)
                ->orderBy('created_at', 'asc')
                ->first();

            if ($firstOwner) {
                \DB::table('owner_store')
                    ->where('store_id', $duplicate->store_id)
                    ->where('id', '!=', $firstOwner->id)
                    ->delete();
            }
        }

        // Add unique constraint on store_id to ensure one store = one owner
        Schema::table('owner_store', function (Blueprint $table) {
            $table->unique('store_id', 'unq_owner_store_store_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('owner_store', function (Blueprint $table) {
            $table->dropUnique('unq_owner_store_store_id');
        });
    }
};

