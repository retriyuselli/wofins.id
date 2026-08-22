<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('companies')) {
            return;
        }

        if (! Schema::hasColumn('companies', 'slug')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->string('slug', 120)->nullable()->after('company_name');
            });
        }

        $used = [];

        DB::table('companies')
            ->select('id', 'company_name', 'slug')
            ->orderBy('id')
            ->get()
            ->each(function ($row) use (&$used) {
                if (filled($row->slug)) {
                    $used[strtolower((string) $row->slug)] = true;

                    return;
                }

                $base = Str::slug((string) $row->company_name) ?: 'wo-'.$row->id;
                $slug = $base;
                $i = 2;

                while (
                    isset($used[strtolower($slug)])
                    || DB::table('companies')->where('slug', $slug)->where('id', '!=', $row->id)->exists()
                ) {
                    $slug = $base.'-'.$i;
                    $i++;
                }

                $used[strtolower($slug)] = true;
                DB::table('companies')->where('id', $row->id)->update(['slug' => $slug]);
            });

        $hasUnique = collect(Schema::getIndexes('companies'))
            ->contains(fn (array $index) => ($index['name'] ?? '') === 'companies_slug_unique'
                || (($index['unique'] ?? false) && ($index['columns'] ?? []) === ['slug']));

        if (! $hasUnique) {
            Schema::table('companies', function (Blueprint $table) {
                $table->unique('slug', 'companies_slug_unique');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('companies') || ! Schema::hasColumn('companies', 'slug')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            $indexes = collect(Schema::getIndexes('companies'));
            if ($indexes->contains(fn (array $index) => ($index['name'] ?? '') === 'companies_slug_unique')) {
                $table->dropUnique('companies_slug_unique');
            }
            $table->dropColumn('slug');
        });
    }
};
