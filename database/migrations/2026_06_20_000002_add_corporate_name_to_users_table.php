<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'corporate_name')) {
                // The owner's corporate / business name — used to brand the
                // dashboard when no logo is uploaded.
                $table->string('corporate_name')->nullable()->after('logo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'corporate_name')) {
                $table->dropColumn('corporate_name');
            }
        });
    }
};
