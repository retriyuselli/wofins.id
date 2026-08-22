<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ProspectApp;
use App\Policies\Concerns\ChecksCompanyOwnership;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ProspectAppPolicy
{
    use ChecksCompanyOwnership;
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can("ViewAny:ProspectApp");
    }

    public function view(AuthUser $authUser, ProspectApp $prospectApp): bool
    {
        return $authUser->can("View:ProspectApp")
            && $this->ownsRecordCompany($prospectApp);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can("Create:ProspectApp");
    }

    public function update(AuthUser $authUser, ProspectApp $prospectApp): bool
    {
        return $authUser->can("Update:ProspectApp")
            && $this->ownsRecordCompany($prospectApp);
    }

    public function delete(AuthUser $authUser, ProspectApp $prospectApp): bool
    {
        return $authUser->can("Delete:ProspectApp")
            && $this->ownsRecordCompany($prospectApp);
    }

    public function restore(AuthUser $authUser, ProspectApp $prospectApp): bool
    {
        return $authUser->can("Restore:ProspectApp")
            && $this->ownsRecordCompany($prospectApp);
    }

    public function forceDelete(AuthUser $authUser, ProspectApp $prospectApp): bool
    {
        return $authUser->can("ForceDelete:ProspectApp")
            && $this->ownsRecordCompany($prospectApp);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can("ForceDeleteAny:ProspectApp");
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can("RestoreAny:ProspectApp");
    }

    public function replicate(AuthUser $authUser, ProspectApp $prospectApp): bool
    {
        return $authUser->can("Replicate:ProspectApp")
            && $this->ownsRecordCompany($prospectApp);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can("Reorder:ProspectApp");
    }
}
