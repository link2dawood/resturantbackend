<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'logo')) {
                // Business logo for owners — shown on the dashboard shell in place
                // of the default brand logo. Stored as a filename on the 'public' disk.
                $table->string('logo')->nullable()->after('avatar');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'logo')) {
                $table->dropColumn('logo');
            }
        });
    }
};
