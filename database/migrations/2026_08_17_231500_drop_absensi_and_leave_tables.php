<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop absensi / cuti / jadwal tables (fitur dihapus).
     */
    public function up(): void
    {
        // Disable FK checks: circular refs (absensis ↔ leave_requests, etc.)
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $tables = [
            'leave_balance_histories',
            'leave_requests',
            'leave_balances',
            'leave_types',
            'koreksi_absensis',
            'log_absensis',
            'pengajuan_lemburs',
            'absensis',
            'lokasi_absensis',
            'pengaturan_absensis',
            'penugasan_jadwals',
            'hari_jadwal_kerjas',
            'jadwal_kerjas',
            'hari_liburs',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                Schema::drop($table);
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'annual_leave_quota')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('annual_leave_quota');
            });
        }
    }

    public function down(): void
    {
        // Irreversible hard-delete cleanup — recreate via older migrations if needed.
    }
};
