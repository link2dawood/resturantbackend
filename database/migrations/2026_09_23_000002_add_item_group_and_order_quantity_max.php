<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5 Part 2, from the 2026-09-22 client meeting.
 *
 * `item_group` clusters related items on the count screen, so 8" and 10" bread
 * sit together under one heading instead of drifting apart in a long list.
 *
 * `order_quantity_max` is the top of the order dropdown. The client asked for
 * whole numbers from a list rather than a decimal box: most items 1 to 5, bread
 * 1 to 10, steak and hamburger meat 1 to 20. Holding it per item means an
 * admin can change it without a release, which is the same reason portions per
 * box are editable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            if (! Schema::hasColumn('inventory_items', 'item_group')) {
                $table->string('item_group', 80)->nullable()->after('inventory_category_id');
            }
            if (! Schema::hasColumn('inventory_items', 'order_quantity_max')) {
                $table->unsignedSmallInteger('order_quantity_max')->nullable()->after('units_per_purchase');
            }
        });

        // Bread is the grouping the client named. The rest wait for their list.
        DB::table('inventory_items')
            ->where(function ($q) {
                $q->whereRaw("LOWER(name) LIKE ?", ['%bread%'])
                    ->orWhereRaw("LOWER(name) LIKE ?", ['%tortilla%'])
                    ->orWhereRaw("LOWER(name) LIKE ?", ['%pita%']);
            })
            ->whereNull('item_group')
            ->update(['item_group' => 'Bread & Wraps']);

        // Order ceilings. Anything already carrying one is left alone.
        $this->setMax(20, ['steak', 'hamburger meat']);

        DB::table('inventory_items')
            ->whereNull('order_quantity_max')
            ->where('item_group', 'Bread & Wraps')
            ->update(['order_quantity_max' => 10]);

        DB::table('inventory_items')
            ->whereNull('order_quantity_max')
            ->update(['order_quantity_max' => 5]);
    }

    /** @param  array<int, string>  $names */
    private function setMax(int $max, array $names): void
    {
        foreach ($names as $name) {
            DB::table('inventory_items')
                ->whereNull('order_quantity_max')
                ->whereRaw('LOWER(name) = ?', [$name])
                ->update(['order_quantity_max' => $max]);
        }
    }

    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_items', 'item_group')) {
                $table->dropColumn('item_group');
            }
            if (Schema::hasColumn('inventory_items', 'order_quantity_max')) {
                $table->dropColumn('order_quantity_max');
            }
        });
    }
};
