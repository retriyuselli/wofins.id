<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin \App\Models\User */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'name' => $this->name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'address' => $this->address,
            'date_of_birth' => optional($this->date_of_birth)?->toDateString(),
            'gender' => $this->gender,
            'department' => $this->department,
            'hire_date' => optional($this->hire_date)?->toDateString(),
            'emergency_contact' => $this->emergency_contact,
            'notes' => $this->notes,
            'status' => $this->status,
            'last_working_date' => optional($this->last_working_date)?->toDateString(),
            'avatar_url' => $this->publicMediaUrl($this->avatar_url),
            'roles' => $this->whenLoaded('roles', fn () => $this->getRoleNames()->values()->all()),
            'expire_date' => optional($this->expire_date)?->toIso8601String(),
            'is_expired' => $this->isExpired(),
            'is_expiring_soon' => $this->isExpiringSoon(),
            'days_until_expiration' => $this->getDaysUntilExpiration(),
            'is_subscription_expired' => $this->isCompanySubscriptionExpired(),
            'can_manage_subscription' => \App\Support\CompanySubscription::canManageSubscription($this->resource),
            'subscription_expires_label' => \App\Support\CompanySubscription::expiresAtLabel($this->resource),
            'company' => $this->companyPayload(),
            'entitlements' => $this->entitlementsPayload(),
        ];
    }

    private function isCompanySubscriptionExpired(): bool
    {
        if (method_exists($this->resource, 'hasRole') && $this->resource->hasRole('super_admin')) {
            return false;
        }

        return \App\Support\CompanySubscription::isExpired($this->resource);
    }

    /**
     * @return array{plan: string, plan_label: string, features: list<string>, seat_limit: int|null}
     */
    private function entitlementsPayload(): array
    {
        $unlocked = \App\Support\ProFeatures::forceUnlocked()
            || (method_exists($this->resource, 'hasRole') && $this->resource->hasRole('super_admin'));

        $planKey = \App\Support\PricingPlans::normalizeKey($this->company?->subscription_plan)
            ?? \App\Support\CompanySubscription::planKey();

        $features = $unlocked
            ? \App\Support\PricingPlans::featureKeys()
            : array_values(array_filter(
                \App\Support\PricingPlans::featureKeys(),
                fn (string $feature) => \App\Support\PricingPlans::allows($planKey, $feature)
            ));

        $plan = \App\Support\PricingPlans::find($planKey);

        return [
            'plan' => $planKey,
            'plan_label' => \App\Support\PricingPlans::shortLabel($planKey),
            'features' => $features,
            'seat_limit' => $unlocked ? null : ($plan['seat_limit'] ?? null),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function companyPayload(): ?array
    {
        $company = $this->company;

        if (! $company) {
            return null;
        }

        $logoUrl = $this->publicMediaUrl($company->logo_url);

        return [
            'id' => (int) $company->id,
            'name' => $company->company_name,
            'inisial' => $company->inisial_wo,
            'logo_url' => $logoUrl,
            'email' => $company->email,
            'phone' => $company->phone,
            'address' => $company->address,
            'city' => $company->city,
            'province' => $company->province,
            'website' => $company->website,
            'description' => $company->description,
            'owner_name' => $company->owner_name,
            'jabatan_owner' => $company->jabatan_owner,
            'established_year' => $company->established_year,
            'is_active' => $company->isActive(),
            'subscription_plan' => $company->subscription_plan,
            'subscription_label' => \App\Support\PricingPlans::shortLabel($company->subscription_plan),
            'subscription_expires_at' => optional($company->subscription_expires_at)?->toIso8601String(),
        ];
    }

    private function publicMediaUrl(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        // Avatar/logo selalu di disk public, bukan default FILESYSTEM_DISK (bisa local/private).
        return url(Storage::disk('public')->url(ltrim($path, '/')));
    }
}
