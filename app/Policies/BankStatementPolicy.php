<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\BankStatement;
use App\Policies\Concerns\ChecksCompanyOwnership;
use App\Support\PricingPlans;
use App\Support\ProFeatures;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class BankStatementPolicy
{
    use ChecksCompanyOwnership;
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return ProFeatures::tenantAllows(PricingPlans::FEATURE_RECONCILIATION)
            || $authUser->can('ViewAny:BankStatement');
    }

    public function view(AuthUser $authUser, BankStatement $bankStatement): bool
    {
        if (! $this->ownsRecordCompany($bankStatement)) {
            return false;
        }

        return ProFeatures::tenantAllows(PricingPlans::FEATURE_RECONCILIATION)
            || $authUser->can('View:BankStatement');
    }

    public function create(AuthUser $authUser): bool
    {
        return ProFeatures::tenantAllows(PricingPlans::FEATURE_RECONCILIATION)
            || $authUser->can('Create:BankStatement');
    }

    public function update(AuthUser $authUser, BankStatement $bankStatement): bool
    {
        if (! $this->ownsRecordCompany($bankStatement)) {
            return false;
        }

        return ProFeatures::tenantAllows(PricingPlans::FEATURE_RECONCILIATION)
            || $authUser->can('Update:BankStatement');
    }

    public function delete(AuthUser $authUser, BankStatement $bankStatement): bool
    {
        if (! $this->ownsRecordCompany($bankStatement)) {
            return false;
        }

        return ProFeatures::tenantAllows(PricingPlans::FEATURE_RECONCILIATION)
            || $authUser->can('Delete:BankStatement');
    }

    public function restore(AuthUser $authUser, BankStatement $bankStatement): bool
    {
        if (! $this->ownsRecordCompany($bankStatement)) {
            return false;
        }

        return ProFeatures::tenantAllows(PricingPlans::FEATURE_RECONCILIATION)
            || $authUser->can('Restore:BankStatement');
    }

    public function forceDelete(AuthUser $authUser, BankStatement $bankStatement): bool
    {
        if (! $this->ownsRecordCompany($bankStatement)) {
            return false;
        }

        return ProFeatures::tenantAllows(PricingPlans::FEATURE_RECONCILIATION)
            || $authUser->can('ForceDelete:BankStatement');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return ProFeatures::tenantAllows(PricingPlans::FEATURE_RECONCILIATION)
            || $authUser->can('ForceDeleteAny:BankStatement');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return ProFeatures::tenantAllows(PricingPlans::FEATURE_RECONCILIATION)
            || $authUser->can('RestoreAny:BankStatement');
    }

    public function replicate(AuthUser $authUser, BankStatement $bankStatement): bool
    {
        return $authUser->can("Replicate:BankStatement")
            && $this->ownsRecordCompany($bankStatement);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can("Reorder:BankStatement");
    }
}
