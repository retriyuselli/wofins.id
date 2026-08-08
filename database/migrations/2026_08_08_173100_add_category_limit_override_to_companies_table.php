<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('companies')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'category_limit_override')) {
                $table->unsignedInteger('category_limit_override')
                    ->nullable()
                    ->after('pembayaran_piutang_limit_override');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('companies') && Schema::hasColumn('companies', 'category_limit_override')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropColumn('category_limit_override');
            });
        }
    }
};
