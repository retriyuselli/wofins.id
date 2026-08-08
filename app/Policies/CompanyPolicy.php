<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Company;
use App\Models\User;
use App\Support\ProFeatures;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class CompanyPolicy
{
    use HandlesAuthorization;

    private function ownsCompany(AuthUser $authUser, Company $company): bool
    {
        if (ProFeatures::actorIsSuperAdmin()) {
            return true;
        }

        if (! $authUser instanceof User || ! $authUser->company_id) {
            return false;
        }

        return (int) $authUser->company_id === (int) $company->id;
    }

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Company');
    }

    public function view(AuthUser $authUser, Company $company): bool
    {
        return $authUser->can('View:Company') && $this->ownsCompany($authUser, $company);
    }

    public function create(AuthUser $authUser): bool
    {
        return ProFeatures::actorIsSuperAdmin() && $authUser->can('Create:Company');
    }

    public function update(AuthUser $authUser, Company $company): bool
    {
        return $authUser->can('Update:Company') && $this->ownsCompany($authUser, $company);
    }

    public function delete(AuthUser $authUser, Company $company): bool
    {
        return ProFeatures::actorIsSuperAdmin() && $authUser->can('Delete:Company');
    }

    public function restore(AuthUser $authUser, Company $company): bool
    {
        return ProFeatures::actorIsSuperAdmin() && $authUser->can('Restore:Company');
    }

    public function forceDelete(AuthUser $authUser, Company $company): bool
    {
        return ProFeatures::actorIsSuperAdmin() && $authUser->can('ForceDelete:Company');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return ProFeatures::actorIsSuperAdmin() && $authUser->can('ForceDeleteAny:Company');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return ProFeatures::actorIsSuperAdmin() && $authUser->can('RestoreAny:Company');
    }

    public function replicate(AuthUser $authUser, Company $company): bool
    {
        return ProFeatures::actorIsSuperAdmin() && $authUser->can('Replicate:Company');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return ProFeatures::actorIsSuperAdmin() && $authUser->can('Reorder:Company');
    }
}
