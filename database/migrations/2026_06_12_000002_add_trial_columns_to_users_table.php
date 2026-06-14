<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 4 — SaaS trial system.
     *
     * Trial state lives on the owner (the workspace/billing entity). Managers
     * inherit access from their owner; admins and the franchisor are exempt.
     *
     * subscription_status values:
     *   trialing → within (or past) a free trial window, gated by trial_ends_at
     *   active   → paid OR internally provisioned; never trial-locked
     *   expired  → trial lapsed (set by the trials:check command on notify)
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('subscription_status', 20)->default('active')->after('role');
            $table->timestamp('trial_started_at')->nullable()->after('subscription_status');
            $table->timestamp('trial_ends_at')->nullable()->after('trial_started_at');
            $table->timestamp('trial_extension_requested_at')->nullable()->after('trial_ends_at');
            $table->timestamp('trial_expiring_notified_at')->nullable()->after('trial_extension_requested_at');
            $table->timestamp('trial_expired_notified_at')->nullable()->after('trial_expiring_notified_at');
        });

        // Grandfather every pre-existing account as 'active' so enabling the trial
        // lockout never locks out current users. New self-serve signups created
        // after this migration call startTrial() and become 'trialing'.
        DB::table('users')->update(['subscription_status' => 'active']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'subscription_status',
                'trial_started_at',
                'trial_ends_at',
                'trial_extension_requested_at',
                'trial_expiring_notified_at',
                'trial_expired_notified_at',
            ]);
        });
    }
};
