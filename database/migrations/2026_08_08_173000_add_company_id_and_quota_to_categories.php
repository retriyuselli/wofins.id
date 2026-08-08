<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('categories')) {
            return;
        }

        Schema::table('categories', function (Blueprint $table) {
            if (! Schema::hasColumn('categories', 'company_id')) {
                $table->foreignId('company_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('companies')
                    ->nullOnDelete();
            }
        });

        // Slug unik per company (bukan global).
        Schema::table('categories', function (Blueprint $table) {
            try {
                $table->dropUnique(['slug']);
            } catch (\Throwable) {
                // Index name may differ across environments.
                try {
                    $sm = Schema::getConnection()->getDoctrineSchemaManager();
                } catch (\Throwable) {
                    // ignore
                }

                $indexes = DB::select('SHOW INDEX FROM categories WHERE Column_name = ? AND Non_unique = 0', ['slug']);
                foreach ($indexes as $index) {
                    $key = $index->Key_name ?? null;
                    if ($key && $key !== 'PRIMARY') {
                        DB::statement("ALTER TABLE categories DROP INDEX `{$key}`");
                    }
                }
            }
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->unique(['company_id', 'slug'], 'categories_company_slug_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('categories')) {
            return;
        }

        Schema::table('categories', function (Blueprint $table) {
            try {
                $table->dropUnique('categories_company_slug_unique');
            } catch (\Throwable) {
                //
            }

            if (Schema::hasColumn('categories', 'company_id')) {
                $table->dropConstrainedForeignId('company_id');
            }
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->unique('slug');
        });
    }
};
