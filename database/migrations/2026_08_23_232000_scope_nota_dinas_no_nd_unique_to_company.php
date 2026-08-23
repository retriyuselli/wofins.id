<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('nota_dinas') || ! Schema::hasColumn('nota_dinas', 'no_nd')) {
            return;
        }

        $indexes = collect(Schema::getIndexes('nota_dinas'));

        foreach ($indexes as $index) {
            $columns = $index['columns'] ?? [];
            $name = $index['name'] ?? '';
            $unique = (bool) ($index['unique'] ?? false);

            if ($unique && $columns === ['no_nd'] && $name !== '') {
                Schema::table('nota_dinas', function (Blueprint $table) use ($name) {
                    $table->dropUnique($name);
                });
            }
        }

        $indexes = collect(Schema::getIndexes('nota_dinas'));
        $hasCompanyScoped = $indexes->contains(
            fn (array $index) => ($index['name'] ?? '') === 'nota_dinas_company_no_nd_unique'
        );

        if (! $hasCompanyScoped && Schema::hasColumn('nota_dinas', 'company_id')) {
            Schema::table('nota_dinas', function (Blueprint $table) {
                $table->unique(['company_id', 'no_nd'], 'nota_dinas_company_no_nd_unique');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('nota_dinas')) {
            return;
        }

        Schema::table('nota_dinas', function (Blueprint $table) {
            $indexes = collect(Schema::getIndexes('nota_dinas'));

            if ($indexes->contains(fn (array $index) => ($index['name'] ?? '') === 'nota_dinas_company_no_nd_unique')) {
                $table->dropUnique('nota_dinas_company_no_nd_unique');
            }

            $hasGlobal = $indexes->contains(function (array $index) {
                return ($index['unique'] ?? false) && ($index['columns'] ?? []) === ['no_nd'];
            });

            if (! $hasGlobal) {
                $table->unique('no_nd');
            }
        });
    }
};
