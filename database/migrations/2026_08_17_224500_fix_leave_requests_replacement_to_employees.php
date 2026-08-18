<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('leave_requests')) {
            return;
        }

        // Lepas FK lama ke users.
        $this->dropForeignIfExists('leave_requests', 'replacement_employee_id');

        // Map user.id → employees.id (via employees.user_id).
        if (Schema::hasTable('employees') && Schema::hasColumn('employees', 'user_id')) {
            DB::statement('
                UPDATE leave_requests lr
                INNER JOIN employees e ON e.user_id = lr.replacement_employee_id
                SET lr.replacement_employee_id = e.id
                WHERE lr.replacement_employee_id IS NOT NULL
            ');

            // Yang tidak punya Employee: null-kan (hindari orphan FK).
            $validIds = DB::table('employees')->pluck('id')->all();
            if ($validIds === []) {
                DB::table('leave_requests')
                    ->whereNotNull('replacement_employee_id')
                    ->update(['replacement_employee_id' => null]);
            } else {
                DB::table('leave_requests')
                    ->whereNotNull('replacement_employee_id')
                    ->whereNotIn('replacement_employee_id', $validIds)
                    ->update(['replacement_employee_id' => null]);
            }
        } else {
            DB::table('leave_requests')
                ->whereNotNull('replacement_employee_id')
                ->update(['replacement_employee_id' => null]);
        }

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->foreign('replacement_employee_id')
                ->references('id')
                ->on('employees')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('leave_requests')) {
            return;
        }

        $this->dropForeignIfExists('leave_requests', 'replacement_employee_id');

        // Kembalikan ke users bila memungkinkan (employee.user_id).
        if (Schema::hasTable('employees')) {
            DB::statement('
                UPDATE leave_requests lr
                INNER JOIN employees e ON e.id = lr.replacement_employee_id
                SET lr.replacement_employee_id = e.user_id
                WHERE lr.replacement_employee_id IS NOT NULL
            ');

            DB::table('leave_requests')
                ->whereNotNull('replacement_employee_id')
                ->whereNotIn('replacement_employee_id', function ($q) {
                    $q->select('id')->from('users');
                })
                ->update(['replacement_employee_id' => null]);
        }

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->foreign('replacement_employee_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    private function dropForeignIfExists(string $table, string $column): void
    {
        $db = DB::getDatabaseName();
        $fk = DB::selectOne(
            'SELECT CONSTRAINT_NAME AS name
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ?
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
               AND REFERENCED_TABLE_NAME IS NOT NULL
             LIMIT 1',
            [$db, $table, $column]
        );

        if (! $fk?->name) {
            return;
        }

        try {
            DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$fk->name}`");
        } catch (\Throwable) {
            //
        }
    }
};
