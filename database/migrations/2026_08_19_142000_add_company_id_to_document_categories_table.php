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

        if (! Schema::hasColumn('document_categories', 'company_id')) {
            Schema::table('document_categories', function (Blueprint $table) {
                $table->foreignId('company_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('companies')
                    ->nullOnDelete();
            });
        }

        $this->rewriteFormats();
        $this->assignToCompanies();

        $hasDuplicateCodes = DB::table('document_categories')
            ->select('company_id', 'code')
            ->groupBy('company_id', 'code')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if (! $hasDuplicateCodes && ! $this->hasIndex('document_categories', 'document_categories_company_id_code_unique')) {
            Schema::table('document_categories', function (Blueprint $table) {
                $table->unique(['company_id', 'code'], 'document_categories_company_id_code_unique');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('document_categories')) {
            return;
        }

        if ($this->hasIndex('document_categories', 'document_categories_company_id_code_unique')) {
            Schema::table('document_categories', function (Blueprint $table) {
                $table->dropUnique('document_categories_company_id_code_unique');
            });
        }

        if (Schema::hasColumn('document_categories', 'company_id')) {
            Schema::table('document_categories', function (Blueprint $table) {
                $table->dropConstrainedForeignId('company_id');
            });
        }
    }

    private function rewriteFormats(): void
    {
        $rows = DB::table('document_categories')->select('id', 'format_number')->get();

        foreach ($rows as $row) {
            $format = $this->formatWithCompanyToken($row->format_number);
            if ($format !== $row->format_number) {
                DB::table('document_categories')->where('id', $row->id)->update([
                    'format_number' => $format,
                ]);
            }
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

    private function assignToCompanies(): void
    {
        if (! Schema::hasTable('companies')) {
            return;
        }

        $companyIds = DB::table('companies')->orderBy('id')->pluck('id')->map(fn ($id) => (int) $id)->all();
        if ($companyIds === []) {
            return;
        }

        $categories = DB::table('document_categories')->whereNull('deleted_at')->orderBy('id')->get();
        if ($categories->isEmpty()) {
            return;
        }

        if (count($companyIds) === 1) {
            DB::table('document_categories')
                ->whereNull('company_id')
                ->update(['company_id' => $companyIds[0]]);

            return;
        }

        $idMap = [];

        foreach ($categories as $category) {
            foreach ($companyIds as $index => $companyId) {
                if ($index === 0) {
                    DB::table('document_categories')->where('id', $category->id)->update([
                        'company_id' => $companyId,
                    ]);
                    $idMap[(int) $category->id][$companyId] = (int) $category->id;

                    continue;
                }

                $newId = (int) DB::table('document_categories')->insertGetId([
                    'company_id' => $companyId,
                    'name' => $category->name,
                    'code' => $category->code,
                    'type' => $category->type,
                    'format_number' => $this->formatWithCompanyToken($category->format_number),
                    'parent_id' => null,
                    'is_approval_required' => $category->is_approval_required,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $idMap[(int) $category->id][$companyId] = $newId;
            }
        }

        foreach ($categories as $category) {
            if (! $category->parent_id) {
                continue;
            }

            foreach ($companyIds as $companyId) {
                $newId = $idMap[(int) $category->id][$companyId] ?? null;
                $newParent = $idMap[(int) $category->parent_id][$companyId] ?? null;
                if ($newId && $newParent) {
                    DB::table('document_categories')->where('id', $newId)->update([
                        'parent_id' => $newParent,
                    ]);
                }
            }
        }

        if (! Schema::hasTable('documents') || ! Schema::hasTable('users')) {
            return;
        }

        $documents = DB::table('documents')->select('id', 'category_id', 'created_by')->get();
        foreach ($documents as $document) {
            $userCompanyId = $document->created_by
                ? (int) (DB::table('users')->where('id', $document->created_by)->value('company_id') ?: 0)
                : 0;

            if ($userCompanyId < 1) {
                continue;
            }

            $newCategoryId = $idMap[(int) $document->category_id][$userCompanyId] ?? null;
            if ($newCategoryId && (int) $newCategoryId !== (int) $document->category_id) {
                DB::table('documents')->where('id', $document->id)->update([
                    'category_id' => $newCategoryId,
                ]);
            }
        }
    }

    private function hasIndex(string $table, string $index): bool
    {
        $indexes = Schema::getIndexes($table);

        foreach ($indexes as $item) {
            if (($item['name'] ?? '') === $index) {
                return true;
            }
        }

        return false;
    }
};
