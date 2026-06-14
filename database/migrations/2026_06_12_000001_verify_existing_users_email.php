<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Phase 4 — SaaS conversion.
     *
     * The app now enforces the `verified` middleware on every authenticated route.
     * Before this change email verification was never wired up, so existing users
     * (admins, owners, managers created prior to Phase 4) may have a NULL
     * `email_verified_at` and would be locked out the moment enforcement goes live.
     *
     * Grandfather them in: every pre-existing account is treated as verified. New
     * self-serve signups created after this migration still go through the real
     * verification flow because they are inserted with a NULL timestamp.
     */
    public function up(): void
    {
        DB::table('users')
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => now()]);
    }

    /**
     * Non-reversible by design: we cannot know which rows were originally NULL,
     * and re-nulling them would lock real users out. Intentionally a no-op.
     */
    public function down(): void
    {
        // no-op
    }
};
