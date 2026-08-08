<?php

use App\Models\Company;
use App\Models\ProspectApp;
use App\Models\User;
use App\Support\PricingPlans;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'company_id')) {
                $table->foreignId('company_id')
                    ->nullable()
                    ->after('created_by')
                    ->constrained('companies')
                    ->nullOnDelete();
            }
        });

        Schema::table('prospect_apps', function (Blueprint $table) {
            if (! Schema::hasColumn('prospect_apps', 'company_id')) {
                $table->foreignId('company_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('companies')
                    ->nullOnDelete();
            }
        });

        $this->backfillTenantCompanies();
    }

    public function down(): void
    {
        Schema::table('prospect_apps', function (Blueprint $table) {
            if (Schema::hasColumn('prospect_apps', 'company_id')) {
                $table->dropConstrainedForeignId('company_id');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'company_id')) {
                $table->dropConstrainedForeignId('company_id');
            }
        });
    }

    /**
     * Satu root tim (created_by null, punya role selain super_admin) → 1 Company.
     * Anggota (created_by = root) ikut company root.
     */
    private function backfillTenantCompanies(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('companies')) {
            return;
        }

        $roots = User::query()
            ->whereNull('company_id')
            ->where(function ($q) {
                $q->whereNull('created_by')->orWhere('created_by', 0);
            })
            ->whereHas('roles', function ($q) {
                $q->where('name', '!=', 'super_admin');
            })
            ->get();

        foreach ($roots as $root) {
            /** @var User $root */
            if ($root->hasRole('super_admin') && $root->roles()->count() === 1) {
                continue;
            }

            $prospect = ProspectApp::query()
                ->where(function ($q) use ($root) {
                    $q->where('user_id', $root->id)
                        ->orWhere('email', $root->email);
                })
                ->latest('id')
                ->first();

            $plan = PricingPlans::normalizeKey($prospect?->service) ?? 'starter';

            $company = Company::query()->create([
                'company_name' => $prospect?->company_name
                    ?: ($root->name.' WO'),
                'business_license' => 'TEMP-'.$root->id.'-'.now()->format('YmdHis'),
                'owner_name' => $prospect?->full_name ?: $root->name,
                'email' => $prospect?->email ?: $root->email,
                'phone' => $prospect?->phone ?: $root->phone_number ?: '-',
                'website' => $prospect?->name_of_website,
                'subscription_plan' => $plan,
            ]);

            $root->forceFill(['company_id' => $company->id])->save();

            User::query()
                ->where('created_by', $root->id)
                ->whereNull('company_id')
                ->update(['company_id' => $company->id]);

            if ($prospect) {
                $prospect->forceFill(['company_id' => $company->id])->save();
            }
        }
    }
};
