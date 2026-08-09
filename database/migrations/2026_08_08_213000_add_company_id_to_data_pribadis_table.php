<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('data_pribadis')) {
            return;
        }

        Schema::table('data_pribadis', function (Blueprint $table) {
            if (! Schema::hasColumn('data_pribadis', 'company_id')) {
                $table->foreignId('company_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('companies')
                    ->nullOnDelete();
            }
        });

        // Email unik per company (bukan global).
        Schema::table('data_pribadis', function (Blueprint $table) {
            try {
                $table->dropUnique(['email']);
            } catch (\Throwable) {
                // Index name may differ across environments.
            }
        });

        // Fallback drop by common MySQL index name.
        try {
            DB::statement('ALTER TABLE data_pribadis DROP INDEX data_pribadis_email_unique');
        } catch (\Throwable) {
            // Already dropped or different name.
        }

        Schema::table('data_pribadis', function (Blueprint $table) {
            $table->unique(['company_id', 'email'], 'data_pribadis_company_email_unique');
        });

        // Backfill ke company yang punya user terbanyak (tenant aktif).
        $companyId = DB::table('users')
            ->whereNotNull('company_id')
            ->select('company_id')
            ->groupBy('company_id')
            ->orderByRaw('COUNT(*) DESC')
            ->value('company_id');

        if (! $companyId) {
            $companyId = DB::table('companies')->orderBy('id')->value('id');
        }

        if ($companyId) {
            DB::table('data_pribadis')
                ->whereNull('company_id')
                ->update(['company_id' => $companyId]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('data_pribadis')) {
            return;
        }

        Schema::table('data_pribadis', function (Blueprint $table) {
            try {
                $table->dropUnique('data_pribadis_company_email_unique');
            } catch (\Throwable) {
                //
            }
        });

        Schema::table('data_pribadis', function (Blueprint $table) {
            $table->unique('email');
        });

        if (Schema::hasColumn('data_pribadis', 'company_id')) {
            Schema::table('data_pribadis', function (Blueprint $table) {
                $table->dropConstrainedForeignId('company_id');
            });
        }
    }
};
