<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('employees')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'tunjangan')) {
                $table->unsignedBigInteger('tunjangan')->nullable()->default(0)->after('salary');
            }
        });

        // Backfill dari user.tunjangan_base bila ada.
        if (Schema::hasColumn('users', 'tunjangan_base')) {
            DB::statement('
                UPDATE employees e
                INNER JOIN users u ON u.id = e.user_id
                SET e.tunjangan = COALESCE(u.tunjangan_base, 0)
                WHERE (e.tunjangan IS NULL OR e.tunjangan = 0)
                  AND u.tunjangan_base IS NOT NULL
                  AND u.tunjangan_base > 0
            ');
        }

        DB::table('employees')->whereNull('tunjangan')->update(['tunjangan' => 0]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('employees') || ! Schema::hasColumn('employees', 'tunjangan')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('tunjangan');
        });
    }
};
