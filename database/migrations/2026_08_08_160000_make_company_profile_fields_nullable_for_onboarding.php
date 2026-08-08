<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Onboarding tenant: profil legal/alamat boleh dilengkapi belakangan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('business_license')->nullable()->change();
            $table->text('address')->nullable()->change();
            $table->string('city')->nullable()->change();
            $table->string('province')->nullable()->change();
            $table->string('postal_code')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('business_license')->nullable(false)->change();
            $table->text('address')->nullable(false)->change();
            $table->string('city')->nullable(false)->change();
            $table->string('province')->nullable(false)->change();
            $table->string('postal_code')->nullable(false)->change();
        });
    }
};
