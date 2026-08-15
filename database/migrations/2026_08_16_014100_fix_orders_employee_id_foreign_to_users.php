<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Event Manager di form Order memakai users.id, tetapi FK lama
 * orders.employee_id masih mereferensikan employees.id → create gagal (1452).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders') || ! Schema::hasColumn('orders', 'employee_id')) {
            return;
        }

        $fk = collect(DB::select("
            SELECT CONSTRAINT_NAME, REFERENCED_TABLE_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'orders'
              AND COLUMN_NAME = 'employee_id'
              AND REFERENCED_TABLE_NAME IS NOT NULL
        "))->first();

        if ($fk && ($fk->REFERENCED_TABLE_NAME ?? null) === 'employees') {
            // Null-kan nilai yang tidak ada di users (data lama / yatim)
            DB::statement('
                UPDATE orders o
                LEFT JOIN users u ON u.id = o.employee_id
                SET o.employee_id = NULL
                WHERE o.employee_id IS NOT NULL AND u.id IS NULL
            ');

            Schema::table('orders', function (Blueprint $table) use ($fk) {
                $table->dropForeign($fk->CONSTRAINT_NAME);
            });
        }

        $stillPointsToUsers = collect(DB::select("
            SELECT CONSTRAINT_NAME, REFERENCED_TABLE_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'orders'
              AND COLUMN_NAME = 'employee_id'
              AND REFERENCED_TABLE_NAME IS NOT NULL
        "))->first();

        if (! $stillPointsToUsers) {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreign('employee_id')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders') || ! Schema::hasColumn('orders', 'employee_id')) {
            return;
        }

        $fk = collect(DB::select("
            SELECT CONSTRAINT_NAME, REFERENCED_TABLE_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'orders'
              AND COLUMN_NAME = 'employee_id'
              AND REFERENCED_TABLE_NAME IS NOT NULL
        "))->first();

        if ($fk && ($fk->REFERENCED_TABLE_NAME ?? null) === 'users') {
            DB::statement('
                UPDATE orders o
                LEFT JOIN employees e ON e.id = o.employee_id
                SET o.employee_id = NULL
                WHERE o.employee_id IS NOT NULL AND e.id IS NULL
            ');

            Schema::table('orders', function (Blueprint $table) use ($fk) {
                $table->dropForeign($fk->CONSTRAINT_NAME);
            });

            Schema::table('orders', function (Blueprint $table) {
                $table->foreign('employee_id')
                    ->references('id')
                    ->on('employees')
                    ->nullOnDelete();
            });
        }
    }
};
