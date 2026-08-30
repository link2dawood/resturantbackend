<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Part 1.5 — how each vendor actually takes an order.
 *
 * Not one of the client's vendors accepts an emailed order:
 *   Coca-Cola  online          Lisanti          phone
 *   Restaurant Depot online or in person        Sam's Club  online or in person
 *   HEB        in person       Walmart          in person
 *   Amazon     online
 *
 * Knowing this per vendor lets the order sheet tell the manager what to do with
 * it: ring Lisanti, carry a printed sheet into HEB, open the website for Coke.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            if (! Schema::hasColumn('vendors', 'order_method')) {
                $table->string('order_method', 20)->nullable()->after('website');
            }

            if (! Schema::hasColumn('vendors', 'order_notes')) {
                $table->string('order_notes', 255)->nullable()->after('order_method');
            }
        });

        // Seed what the client told us, matched on name so it is safe to re-run.
        $known = [
            'Coca-Cola' => 'online',
            'Lisanti' => 'phone',
            'Restaurant Depot' => 'online',
            "Sam's Club" => 'online',
            'HEB' => 'in_person',
            'Walmart' => 'in_person',
            'Amazon' => 'online',
        ];

        foreach ($known as $name => $method) {
            DB::table('vendors')
                ->whereRaw('LOWER(vendor_name) = ?', [mb_strtolower($name)])
                ->whereNull('order_method')
                ->update(['order_method' => $method]);
        }
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            foreach (['order_notes', 'order_method'] as $column) {
                if (Schema::hasColumn('vendors', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
