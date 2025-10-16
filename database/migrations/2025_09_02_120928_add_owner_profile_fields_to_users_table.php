<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Personal Information
            $table->text('home_address')->nullable();
            $table->string('personal_phone')->nullable();
            $table->string('personal_email')->nullable();

            // Corporate Information
            $table->text('corporate_address')->nullable();
            $table->string('corporate_phone')->nullable();
            $table->string('corporate_email')->nullable();
            $table->string('fanns_philly_email')->nullable();

            // Business Details
            $table->string('corporate_ein')->nullable();
            $table->date('corporate_creation_date')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'home_address',
                'personal_phone',
                'personal_email',
                'corporate_address',
                'corporate_phone',
                'corporate_email',
                'fanns_philly_email',
                'corporate_ein',
                'corporate_creation_date',
            ]);
        });
    }
};
