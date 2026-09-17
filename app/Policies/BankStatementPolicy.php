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
        return $this->allows($authUser, 'ViewAny:BankStatement');
    }

    public function view(AuthUser $authUser, BankStatement $bankStatement): bool
    {
        if (! $this->ownsRecordCompany($bankStatement)) {
            return false;
        }

        return $this->allows($authUser, 'View:BankStatement');
    }

    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:BankStatement');
    }

    public function update(AuthUser $authUser, BankStatement $bankStatement): bool
    {
        if (! $this->ownsRecordCompany($bankStatement)) {
            return false;
        }

        return $this->allows($authUser, 'Update:BankStatement');
    }

    public function delete(AuthUser $authUser, BankStatement $bankStatement): bool
    {
        if (! $this->ownsRecordCompany($bankStatement)) {
            return false;
        }

        return $this->allows($authUser, 'Delete:BankStatement');
    }

    public function restore(AuthUser $authUser, BankStatement $bankStatement): bool
    {
        if (! $this->ownsRecordCompany($bankStatement)) {
            return false;
        }

        return $this->allows($authUser, 'Restore:BankStatement');
    }

    public function forceDelete(AuthUser $authUser, BankStatement $bankStatement): bool
    {
        if (! $this->ownsRecordCompany($bankStatement)) {
            return false;
        }

        return $this->allows($authUser, 'ForceDelete:BankStatement');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:BankStatement');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:BankStatement');
    }

    public function replicate(AuthUser $authUser, BankStatement $bankStatement): bool
    {
        return $this->allows($authUser, 'Replicate:BankStatement')
            && $this->ownsRecordCompany($bankStatement);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Reorder:BankStatement');
    }

    private function allows(AuthUser $authUser, string $permission): bool
    {
        if (ProFeatures::actorIsSuperAdmin()) {
            return true;
        }

        return ProFeatures::tenantAllows(PricingPlans::FEATURE_RECONCILIATION)
            && $authUser->can($permission);
    }
}
