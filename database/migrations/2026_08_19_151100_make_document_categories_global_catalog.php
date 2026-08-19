<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('document_categories')) {
            return;
        }

        $groups = DB::table('document_categories')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get()
            ->groupBy(fn ($row) => (string) ($row->code ?? ''));

        foreach ($groups as $rows) {
            $keep = $rows->first();
            $keepId = (int) $keep->id;

            DB::table('document_categories')->where('id', $keepId)->update([
                'company_id' => null,
                'format_number' => $this->formatWithCompanyToken($keep->format_number),
            ]);

            foreach ($rows->skip(1) as $extra) {
                $extraId = (int) $extra->id;

                if (Schema::hasTable('documents')) {
                    DB::table('documents')->where('category_id', $extraId)->update([
                        'category_id' => $keepId,
                    ]);
                }

                DB::table('document_categories')->where('parent_id', $extraId)->update([
                    'parent_id' => $keepId,
                ]);

                DB::table('document_categories')->where('id', $extraId)->delete();
            }
        }

        DB::table('document_categories')->update(['company_id' => null]);

        if ($this->hasIndex('document_categories', 'document_categories_company_id_code_unique')) {
            Schema::table('document_categories', function (Blueprint $table) {
                $table->dropForeign(['company_id']);
            });

            Schema::table('document_categories', function (Blueprint $table) {
                $table->dropUnique('document_categories_company_id_code_unique');
            });

            Schema::table('document_categories', function (Blueprint $table) {
                $table->foreign('company_id')
                    ->references('id')
                    ->on('companies')
                    ->nullOnDelete();
            });
        }

        $hasDuplicateCodes = DB::table('document_categories')
            ->select('code')
            ->whereNull('deleted_at')
            ->groupBy('code')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if (! $hasDuplicateCodes && ! $this->hasIndex('document_categories', 'document_categories_code_unique')) {
            Schema::table('document_categories', function (Blueprint $table) {
                $table->unique('code', 'document_categories_code_unique');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('document_categories')) {
            return;
        }

        if ($this->hasIndex('document_categories', 'document_categories_code_unique')) {
            Schema::table('document_categories', function (Blueprint $table) {
                $table->dropUnique('document_categories_code_unique');
            });
        }

        if (Schema::hasColumn('document_categories', 'company_id')
            && ! $this->hasIndex('document_categories', 'document_categories_company_id_code_unique')) {
            Schema::table('document_categories', function (Blueprint $table) {
                $table->unique(['company_id', 'code'], 'document_categories_company_id_code_unique');
            });
        }
    }

    private function formatWithCompanyToken(?string $format): string
    {
        if (! filled($format)) {
            return '{SEQ}/{CAT}/{CO}/{ROMAN_MONTH}/{Y}';
        }

        $updated = str_replace(['MKI-OUT', '/MKI/', 'MKI'], ['{CO}-OUT', '/{CO}/', '{CO}'], $format);

        return str_replace('{CO}{CO}', '{CO}', $updated);
    }

    private function hasIndex(string $table, string $index): bool
    {
        foreach (Schema::getIndexes($table) as $item) {
            if (($item['name'] ?? '') === $index) {
                return true;
            }
        }

        return false;
    }
};
