<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'payment_method_limit_override')) {
                $table->unsignedInteger('payment_method_limit_override')
                    ->nullable()
                    ->after('simulasi_limit_override');
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'payment_method_limit_override')) {
                $table->dropColumn('payment_method_limit_override');
            }
        });
    }
};
