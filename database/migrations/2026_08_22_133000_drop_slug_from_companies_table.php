<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
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

    public function down(): void
    {
        if (! Schema::hasTable('companies') || Schema::hasColumn('companies', 'slug')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            $table->string('slug', 120)->nullable()->after('company_name');
        });
    }
};
