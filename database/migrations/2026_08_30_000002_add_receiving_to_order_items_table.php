<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Part 1.5 A2 — check the delivery against the order, line by line.
 *
 * The client: "we had a problem a few weeks ago when a vendor was sending more
 * items than what we ordered." Marking a whole order received in one click
 * cannot catch that. Recording what actually arrived per line can.
 *
 * quantity_received is null until someone checks the line in, which keeps
 * "not checked yet" distinct from "checked, none arrived".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('order_items', 'quantity_received')) {
                $table->decimal('quantity_received', 14, 4)->nullable()->after('quantity');
            }

            if (! Schema::hasColumn('order_items', 'received_notes')) {
                $table->string('received_notes', 255)->nullable()->after('quantity_received');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'received_by')) {
                $table->foreignId('received_by')->nullable()->after('received_at')
                    ->constrained('users')->nullOnDelete();
            }
        });

        // Orders already marked received predate the line-by-line check. Treat
        // them as having arrived exactly as ordered, which is what the single
        // button meant at the time, rather than leaving them looking unchecked.
        DB::table('order_items')
            ->whereNull('quantity_received')
            ->whereIn('order_id', DB::table('orders')->where('status', 'received')->pluck('id'))
            ->update(['quantity_received' => DB::raw('quantity')]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'received_by')) {
                $table->dropConstrainedForeignId('received_by');
            }
        });

        Schema::table('order_items', function (Blueprint $table) {
            foreach (['received_notes', 'quantity_received'] as $column) {
                if (Schema::hasColumn('order_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
