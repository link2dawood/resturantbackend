<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Get all managers that have assigned_stores JSON
        $managers = DB::table('users')
            ->where('role', 'manager')
            ->whereNotNull('assigned_stores')
            ->get();

        foreach ($managers as $manager) {
            $storeIds = json_decode($manager->assigned_stores, true);

            if (is_array($storeIds)) {
                foreach ($storeIds as $storeId) {
                    DB::table('manager_store')->updateOrInsert(
                        [
                            'manager_id' => $manager->id,
                            'store_id'   => $storeId,
                        ],
                        [] // no extra data
                    );
                }
            }
        }
    }

    public function down(): void
    {
        // Optional rollback: delete pivot rows that were created
        DB::table('manager_store')->truncate();
    }
};

