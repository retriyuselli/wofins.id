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
            foreach ([
                'data_pembayaran_limit_override' => 'category_limit_override',
                'expense_limit_override' => 'data_pembayaran_limit_override',
                'expense_ops_limit_override' => 'expense_limit_override',
                'pendapatan_lain_limit_override' => 'expense_ops_limit_override',
                'pengeluaran_lain_limit_override' => 'pendapatan_lain_limit_override',
            ] as $column => $after) {
                if (! Schema::hasColumn('companies', $column)) {
                    $table->unsignedInteger($column)->nullable()->after($after);
                }
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
                'data_pembayaran_limit_override',
                'expense_limit_override',
                'expense_ops_limit_override',
                'pendapatan_lain_limit_override',
                'pengeluaran_lain_limit_override',
            ] as $column) {
                if (Schema::hasColumn('companies', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
