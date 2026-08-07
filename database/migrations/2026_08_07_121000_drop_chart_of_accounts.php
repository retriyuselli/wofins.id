<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fixed_assets')) {
            $this->dropForeignIfExists('fixed_assets', 'fixed_assets_chart_of_account_id_foreign');
            $this->dropForeignIfExists('fixed_assets', 'fixed_assets_depreciation_account_id_foreign');

            Schema::table('fixed_assets', function (Blueprint $table) {
                if (Schema::hasColumn('fixed_assets', 'chart_of_account_id')) {
                    $table->dropColumn('chart_of_account_id');
                }

                if (Schema::hasColumn('fixed_assets', 'depreciation_account_id')) {
                    $table->dropColumn('depreciation_account_id');
                }
            });
        }

        Schema::dropIfExists('chart_of_accounts');
    }

    public function down(): void
    {
        // Intentionally left empty: Chart of Account module has been removed.
    }

    private function dropForeignIfExists(string $table, string $constraint): void
    {
        $exists = DB::selectOne(
            'SELECT CONSTRAINT_NAME
             FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND CONSTRAINT_NAME = ?
               AND CONSTRAINT_TYPE = ?',
            [$table, $constraint, 'FOREIGN KEY']
        );

        if ($exists) {
            Schema::table($table, function (Blueprint $blueprint) use ($constraint) {
                $blueprint->dropForeign($constraint);
            });
        }
    }
};
