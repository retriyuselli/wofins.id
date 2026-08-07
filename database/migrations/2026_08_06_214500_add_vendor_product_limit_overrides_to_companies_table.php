<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->unsignedInteger('vendor_limit_override')
                ->nullable()
                ->after('seat_limit_override');

            $table->unsignedInteger('product_limit_override')
                ->nullable()
                ->after('vendor_limit_override');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['vendor_limit_override', 'product_limit_override']);
        });
    }
};
