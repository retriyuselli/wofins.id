<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('companies')) {
            return;
        }

        // Prefer company yang sudah punya user (tenant aktif).
        $companyId = DB::table('users')
            ->whereNotNull('company_id')
            ->select('company_id')
            ->groupBy('company_id')
            ->orderByRaw('COUNT(*) DESC')
            ->value('company_id');

        if (! $companyId) {
            $companyId = DB::table('companies')
                ->whereNotNull('subscription_plan')
                ->where('subscription_plan', '!=', '')
                ->orderByDesc('id')
                ->value('id');
        }

        if (! $companyId) {
            $companyId = DB::table('companies')->orderBy('id')->value('id');
        }

        if (! $companyId) {
            return;
        }

        if (Schema::hasTable('categories') && Schema::hasColumn('categories', 'company_id')) {
            DB::table('categories')
                ->whereNull('company_id')
                ->update(['company_id' => $companyId]);
        }

        if (Schema::hasTable('payment_methods') && Schema::hasColumn('payment_methods', 'company_id')) {
            DB::table('payment_methods')
                ->whereNull('company_id')
                ->update(['company_id' => $companyId]);
        }

        if (Schema::hasTable('fixed_assets') && Schema::hasColumn('fixed_assets', 'company_id')) {
            DB::table('fixed_assets')
                ->whereNull('company_id')
                ->update(['company_id' => $companyId]);
        }
    }

    public function down(): void
    {
        // Irreversible data backfill.
    }
};
