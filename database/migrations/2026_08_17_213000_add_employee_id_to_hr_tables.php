<?php

use App\Models\Employee;
use App\Models\User;
use App\Support\HrEmployee;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * @var list<string>
     */
    private array $tables = [
        'absensis',
        'log_absensis',
        'koreksi_absensis',
        'pengajuan_lemburs',
        'leave_requests',
        'leave_balances',
        'payrolls',
        'penugasan_jadwals',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table) || Schema::hasColumn($table, 'employee_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->foreignId('employee_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('employees')
                    ->cascadeOnDelete();
            });
        }

        $this->backfillEmployeeIds();

        if (Schema::hasTable('absensis') && Schema::hasColumn('absensis', 'employee_id')) {
            $this->replaceAbsensiUniqueIndex();
        }

        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'user_id')) {
                $this->makeUserIdNullable($table);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('absensis')) {
            $this->dropIndexIfExists('absensis', 'absensis_employee_id_tanggal_unique');
        }

        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'employee_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropConstrainedForeignId('employee_id');
            });
        }
    }

    private function replaceAbsensiUniqueIndex(): void
    {
        // Unique (user_id, tanggal) ikut FK user_id di MySQL — drop FK dulu.
        $fk = $this->foreignKeyName('absensis', 'user_id');
        if ($fk) {
            DB::statement("ALTER TABLE `absensis` DROP FOREIGN KEY `{$fk}`");
        }

        $this->dropIndexIfExists('absensis', 'absensis_user_id_tanggal_unique');
        $this->dropIndexIfExists('absensis', 'absensis_user_id_tanggal_index');

        if (! $this->indexExists('absensis', 'absensis_employee_id_tanggal_unique')) {
            Schema::table('absensis', function (Blueprint $table) {
                $table->unique(['employee_id', 'tanggal'], 'absensis_employee_id_tanggal_unique');
            });
        }

        // Re-add nullable user_id FK
        DB::statement('ALTER TABLE `absensis` MODIFY `user_id` BIGINT UNSIGNED NULL');
        if (! $this->foreignKeyName('absensis', 'user_id')) {
            DB::statement(
                'ALTER TABLE `absensis` ADD CONSTRAINT `absensis_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL'
            );
        }
    }

    private function backfillEmployeeIds(): void
    {
        $cache = [];

        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'employee_id') || ! Schema::hasColumn($table, 'user_id')) {
                continue;
            }

            $rows = DB::table($table)->whereNull('employee_id')->whereNotNull('user_id')->get(['id', 'user_id']);

            foreach ($rows as $row) {
                $userId = (int) $row->user_id;
                if (! isset($cache[$userId])) {
                    $user = User::query()->find($userId);
                    $cache[$userId] = $user
                        ? HrEmployee::ensureForUser($user)->id
                        : $this->orphanEmployeeId($userId);
                }

                DB::table($table)->where('id', $row->id)->update([
                    'employee_id' => $cache[$userId],
                ]);
            }
        }
    }

    private function orphanEmployeeId(int $missingUserId): int
    {
        $slug = 'orphan-user-'.$missingUserId;
        $employee = Employee::withTrashed()->where('slug', 'like', $slug.'%')->first();

        if ($employee) {
            return $employee->id;
        }

        return Employee::query()->create([
            'name' => 'Orphan User #'.$missingUserId,
            'slug' => $slug.'-'.Str::lower(Str::random(4)),
            'user_id' => null,
            'note' => 'Auto-created during HR employee_id backfill; source user missing.',
        ])->id;
    }

    private function makeUserIdNullable(string $table): void
    {
        if ($table === 'absensis') {
            return; // sudah ditangani di replaceAbsensiUniqueIndex
        }

        $driver = Schema::getConnection()->getDriverName();
        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        $fk = $this->foreignKeyName($table, 'user_id');
        if ($fk) {
            DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$fk}`");
        }

        DB::statement("ALTER TABLE `{$table}` MODIFY `user_id` BIGINT UNSIGNED NULL");

        if (! $this->foreignKeyName($table, 'user_id')) {
            DB::statement(
                "ALTER TABLE `{$table}` ADD CONSTRAINT `{$table}_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL"
            );
        }
    }

    private function foreignKeyName(string $table, string $column): ?string
    {
        $database = Schema::getConnection()->getDatabaseName();
        $row = DB::selectOne(
            'SELECT CONSTRAINT_NAME as name FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL
             LIMIT 1',
            [$database, $table, $column]
        );

        return $row->name ?? null;
    }

    private function indexExists(string $table, string $index): bool
    {
        $database = Schema::getConnection()->getDatabaseName();
        $exists = DB::selectOne(
            'SELECT 1 as ok FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1',
            [$database, $table, $index]
        );

        return (bool) $exists;
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        if ($this->indexExists($table, $index)) {
            DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$index}`");
        }
    }
};
