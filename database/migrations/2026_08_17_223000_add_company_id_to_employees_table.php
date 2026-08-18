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
            if (! Schema::hasColumn('employees', 'company_id')) {
                $table->foreignId('company_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('companies')
                    ->nullOnDelete();
            }
        });

        // Backfill dari User terkait (ESS).
        if (Schema::hasColumn('users', 'company_id')) {
            DB::statement('
                UPDATE employees e
                INNER JOIN users u ON u.id = e.user_id
                SET e.company_id = u.company_id
                WHERE e.company_id IS NULL
                  AND u.company_id IS NOT NULL
            ');
        }

        // Sisa tanpa user: company dengan user terbanyak, lalu company pertama.
        $fallbackCompanyId = DB::table('users')
            ->whereNotNull('company_id')
            ->select('company_id')
            ->groupBy('company_id')
            ->orderByRaw('COUNT(*) DESC')
            ->value('company_id');

        if (! $fallbackCompanyId) {
            $fallbackCompanyId = DB::table('companies')->orderBy('id')->value('id');
        }

        if ($fallbackCompanyId) {
            DB::table('employees')
                ->whereNull('company_id')
                ->update(['company_id' => $fallbackCompanyId]);
        }

        // Drop unique email global jika ada (nama index bisa berbeda).
        $this->dropIndexIfExists('employees', 'employees_email_unique');
        $this->dropIndexIfExists('employees', 'email');

        if (! $this->indexExists('employees', 'employees_company_email_unique')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->unique(['company_id', 'email'], 'employees_company_email_unique');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('employees')) {
            return;
        }

        $this->dropIndexIfExists('employees', 'employees_company_email_unique');

        if (Schema::hasColumn('employees', 'company_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropConstrainedForeignId('company_id');
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        $db = DB::getDatabaseName();
        $row = DB::selectOne(
            'SELECT 1 AS ok FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
            [$db, $table, $index]
        );

        return (bool) $row;
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        if (! $this->indexExists($table, $index)) {
            return;
        }

        try {
            DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$index}`");
        } catch (\Throwable) {
            //
        }
    }
};
