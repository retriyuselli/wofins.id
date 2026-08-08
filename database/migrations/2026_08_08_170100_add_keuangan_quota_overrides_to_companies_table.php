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
            if (! Schema::hasColumn('companies', 'fixed_asset_limit_override')) {
                $table->unsignedInteger('fixed_asset_limit_override')
                    ->nullable()
                    ->after('payment_method_limit_override');
            }

            if (! Schema::hasColumn('companies', 'piutang_limit_override')) {
                $table->unsignedInteger('piutang_limit_override')
                    ->nullable()
                    ->after('fixed_asset_limit_override');
            }

            if (! Schema::hasColumn('companies', 'pembayaran_piutang_limit_override')) {
                $table->unsignedInteger('pembayaran_piutang_limit_override')
                    ->nullable()
                    ->after('piutang_limit_override');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('companies')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            foreach ([
                'fixed_asset_limit_override',
                'piutang_limit_override',
                'pembayaran_piutang_limit_override',
            ] as $column) {
                if (Schema::hasColumn('companies', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
