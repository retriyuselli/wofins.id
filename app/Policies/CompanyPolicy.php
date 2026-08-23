<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Company;
use App\Policies\Concerns\ChecksCompanyOwnership;
use App\Support\ProFeatures;
use App\Support\UserVisibility;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class CompanyPolicy
{
    use ChecksCompanyOwnership;
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        if (ProFeatures::actorIsSuperAdmin()) {
            return true;
        }

        return UserVisibility::companyId() !== null
            || $authUser->can('ViewAny:Company');
    }

    public function view(AuthUser $authUser, Company $company): bool
    {
        if (ProFeatures::actorIsSuperAdmin()) {
            return true;
        }

        return $this->ownsRecordCompany($company);
    }

    public function create(AuthUser $authUser): bool
    {
        return ProFeatures::actorIsSuperAdmin()
            && $authUser->can('Create:Company');
    }

    public function update(AuthUser $authUser, Company $company): bool
    {
        if (ProFeatures::actorIsSuperAdmin()) {
            return $authUser->can('Update:Company');
        }

        return $this->ownsRecordCompany($company);
    }

    public function delete(AuthUser $authUser, Company $company): bool
    {
        return $authUser->can("Delete:Company")
            && $this->ownsRecordCompany($company);
    }

    public function restore(AuthUser $authUser, Company $company): bool
    {
        return $authUser->can("Restore:Company")
            && $this->ownsRecordCompany($company);
    }

    public function forceDelete(AuthUser $authUser, Company $company): bool
    {
        return $authUser->can("ForceDelete:Company")
            && $this->ownsRecordCompany($company);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can("ForceDeleteAny:Company");
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can("RestoreAny:Company");
    }

    public function replicate(AuthUser $authUser, Company $company): bool
    {
        return $authUser->can("Replicate:Company")
            && $this->ownsRecordCompany($company);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can("Reorder:Company");
    }
}
