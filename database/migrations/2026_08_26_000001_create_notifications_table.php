<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5 Part 1 Task 14 — Laravel's standard notifications table, needed for
 * the in-app bell. The app already sends mail notifications; this adds the
 * 'database' channel so the same notification can also appear in the UI.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notifications')) {
            return;
        }

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            // morphs() already indexes (notifiable_type, notifiable_id), which is
            // what every query here filters on. A wider composite adding read_at
            // pushes the key past MySQL 5.7's 767-byte limit when innodb_large_prefix
            // is off, for no real gain on a table this size.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
