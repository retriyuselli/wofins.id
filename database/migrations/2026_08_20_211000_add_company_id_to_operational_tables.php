<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Isolasi operasional per company: tambah company_id + backfill dari owner/rekening/parent.
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $tables = [
        'orders',
        'vendors',
        'products',
        'prospects',
        'simulasi_produks',
        'piutangs',
        'pembayaran_piutangs',
        'nota_dinas',
        'nota_dinas_details',
        'sops',
        'sop_categories',
        'sop_revisions',
        'documentations',
        'documentation_categories',
        'account_manager_targets',
        'payrolls',
        'expenses',
        'expense_ops',
        'pendapatan_lains',
        'pengeluaran_lains',
        'bank_statements',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            $this->addCompanyId($table);
        }

        $this->backfillFromUser('orders', 'user_id');
        $this->backfillFromUser('vendors', 'created_by');
        $this->backfillFromUser('products', 'created_by');
        $this->backfillFromUser('prospects', 'user_id');
        $this->backfillFromUser('simulasi_produks', 'user_id');
        $this->backfillFromUser('piutangs', 'dibuat_oleh');
        $this->backfillFromUser('nota_dinas', 'pengirim_id');
        $this->backfillFromUser('sops', 'created_by');
        $this->backfillFromUser('account_manager_targets', 'user_id');
        $this->backfillFromUser('bank_statements', 'uploaded_by');

        $this->backfillFromPaymentMethod('expense_ops');
        $this->backfillFromPaymentMethod('pendapatan_lains');
        $this->backfillFromPaymentMethod('pengeluaran_lains');
        $this->backfillFromPaymentMethod('bank_statements');

        $this->backfillFromParent('expenses', 'orders', 'order_id');
        $this->backfillFromParent('pembayaran_piutangs', 'piutangs', 'piutang_id');
        $this->backfillFromParent('nota_dinas_details', 'nota_dinas', 'nota_dinas_id');
        $this->backfillFromParent('sop_revisions', 'sops', 'sop_id');

        $this->backfillPayrolls();
        $this->backfillSopCategoriesFromSops();
    }

    public function down(): void
    {
        foreach (array_reverse($this->tables) as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'company_id')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropConstrainedForeignId('company_id');
                });
            }
        }
    }

    private function addCompanyId(string $table): void
    {
        if (! Schema::hasTable($table) || Schema::hasColumn($table, 'company_id')) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) {
            $blueprint->foreignId('company_id')
                ->nullable()
                ->after('id')
                ->constrained('companies')
                ->nullOnDelete();
        });
    }

    private function backfillFromUser(string $table, string $userColumn): void
    {
        if (! Schema::hasTable($table)
            || ! Schema::hasColumn($table, 'company_id')
            || ! Schema::hasColumn($table, $userColumn)
            || ! Schema::hasColumn('users', 'company_id')) {
            return;
        }

        DB::statement("
            UPDATE {$table}
            INNER JOIN users ON users.id = {$table}.{$userColumn}
            SET {$table}.company_id = users.company_id
            WHERE {$table}.company_id IS NULL
              AND users.company_id IS NOT NULL
        ");
    }

    private function backfillFromPaymentMethod(string $table): void
    {
        if (! Schema::hasTable($table)
            || ! Schema::hasColumn($table, 'company_id')
            || ! Schema::hasColumn($table, 'payment_method_id')
            || ! Schema::hasTable('payment_methods')
            || ! Schema::hasColumn('payment_methods', 'company_id')) {
            return;
        }

        DB::statement("
            UPDATE {$table}
            INNER JOIN payment_methods ON payment_methods.id = {$table}.payment_method_id
            SET {$table}.company_id = payment_methods.company_id
            WHERE {$table}.company_id IS NULL
              AND payment_methods.company_id IS NOT NULL
        ");
    }

    private function backfillFromParent(string $table, string $parentTable, string $fk): void
    {
        if (! Schema::hasTable($table)
            || ! Schema::hasColumn($table, 'company_id')
            || ! Schema::hasColumn($table, $fk)
            || ! Schema::hasTable($parentTable)
            || ! Schema::hasColumn($parentTable, 'company_id')) {
            return;
        }

        DB::statement("
            UPDATE {$table}
            INNER JOIN {$parentTable} ON {$parentTable}.id = {$table}.{$fk}
            SET {$table}.company_id = {$parentTable}.company_id
            WHERE {$table}.company_id IS NULL
              AND {$parentTable}.company_id IS NOT NULL
        ");
    }

    private function backfillPayrolls(): void
    {
        if (! Schema::hasTable('payrolls') || ! Schema::hasColumn('payrolls', 'company_id')) {
            return;
        }

        if (Schema::hasTable('employees') && Schema::hasColumn('employees', 'company_id') && Schema::hasColumn('payrolls', 'employee_id')) {
            DB::statement('
                UPDATE payrolls
                INNER JOIN employees ON employees.id = payrolls.employee_id
                SET payrolls.company_id = employees.company_id
                WHERE payrolls.company_id IS NULL
                  AND employees.company_id IS NOT NULL
            ');
        }

        $this->backfillFromUser('payrolls', 'user_id');
    }

    private function backfillSopCategoriesFromSops(): void
    {
        if (! Schema::hasTable('sop_categories')
            || ! Schema::hasColumn('sop_categories', 'company_id')
            || ! Schema::hasTable('sops')
            || ! Schema::hasColumn('sops', 'company_id')
            || ! Schema::hasColumn('sops', 'category_id')) {
            return;
        }

        // Ambil company_id dari salah satu SOP di kategori (data legacy shared).
        DB::statement('
            UPDATE sop_categories sc
            INNER JOIN (
                SELECT category_id, MIN(company_id) AS company_id
                FROM sops
                WHERE company_id IS NOT NULL AND category_id IS NOT NULL
                GROUP BY category_id
            ) x ON x.category_id = sc.id
            SET sc.company_id = x.company_id
            WHERE sc.company_id IS NULL
        ');
    }
};
