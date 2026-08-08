<?php

namespace App\Services;

use App\Models\Company;
use App\Models\ProspectApp;
use App\Models\User;
use App\Support\CompanySubscription;
use App\Support\PricingPlans;
use Illuminate\Support\Facades\Schema;

/**
 * Onboarding multi-tenant: 1 WO = 1 Company = beberapa user (kuota paket).
 */
class TenantOnboardingService
{
    /**
     * Buat atau tautkan Company untuk pemilik paket (user yang di-Approve).
     * Mengembalikan Company yang dipakai user.
     */
    public function provisionOwnerCompany(User $user, ?ProspectApp $prospect = null): Company
    {
        $prospect ??= $this->findProspectFor($user);

        if ($user->company_id) {
            $company = Company::query()->find($user->company_id);

            if ($company) {
                $this->ensurePlanFromProspect($company, $prospect);
                $this->linkProspect($prospect, $company);

                return $company;
            }
        }

        $plan = PricingPlans::normalizeKey($prospect?->service) ?? CompanySubscription::DEFAULT_PLAN;

        $email = $prospect?->email ?: $user->email;
        $phone = $prospect?->phone ?: $user->phone_number ?: '-';

        // Hindari bentrok unique email / business_license antar tenant.
        if ($email && Company::query()->where('email', $email)->exists()) {
            $email = $user->id.'+'.$email;
        }

        $company = Company::query()->create([
            'company_name' => filled($prospect?->company_name)
                ? $prospect->company_name
                : ($user->name.' WO'),
            'business_license' => 'TEMP-'.$user->id.'-'.now()->format('YmdHis'),
            'owner_name' => $prospect?->full_name ?: $user->name,
            'email' => $email,
            'phone' => $phone,
            'address' => null,
            'city' => null,
            'province' => null,
            'postal_code' => null,
            'website' => $prospect?->name_of_website,
            'subscription_plan' => $plan,
        ]);

        $user->forceFill([
            'company_id' => $company->id,
            'created_by' => null,
        ])->save();

        $this->linkProspect($prospect, $company);
        CompanySubscription::forgetCache($company->id);

        return $company;
    }

    public function findProspectFor(User $user): ?ProspectApp
    {
        if (! Schema::hasTable('prospect_apps')) {
            return null;
        }

        return ProspectApp::query()
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('email', $user->email);
            })
            ->latest('id')
            ->first();
    }

    public function ensurePlanFromProspect(Company $company, ?ProspectApp $prospect): void
    {
        $plan = PricingPlans::normalizeKey($prospect?->service);

        if (! $plan) {
            return;
        }

        if (! PricingPlans::find($company->subscription_plan)) {
            $company->forceFill(['subscription_plan' => $plan])->save();
            CompanySubscription::forgetCache($company->id);
        }
    }

    public function linkProspect(?ProspectApp $prospect, Company $company): void
    {
        if (! $prospect) {
            return;
        }

        $data = ['company_id' => $company->id];

        if (Schema::hasColumn('prospect_apps', 'company_id')) {
            $prospect->forceFill($data)->save();
        }
    }

    /**
     * Stempel company_id anggota tim dari actor (pemilik/anggota perusahaan yang sama).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function stampMemberCompany(array $data, ?User $actor = null): array
    {
        $actor ??= auth()->user();

        if (! $actor instanceof User || ! $actor->company_id) {
            return $data;
        }

        $data['company_id'] = $actor->company_id;

        return $data;
    }
}
