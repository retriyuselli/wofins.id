<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Sop;
use App\Policies\Concerns\ChecksCompanyOwnership;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class SopPolicy
{
    use ChecksCompanyOwnership;
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can("ViewAny:Sop");
    }

    public function view(AuthUser $authUser, Sop $sop): bool
    {
        return $authUser->can("View:Sop")
            && $this->ownsRecordCompany($sop);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can("Create:Sop");
    }

    public function update(AuthUser $authUser, Sop $sop): bool
    {
        return $authUser->can("Update:Sop")
            && $this->ownsRecordCompany($sop);
    }

    public function delete(AuthUser $authUser, Sop $sop): bool
    {
        return $authUser->can("Delete:Sop")
            && $this->ownsRecordCompany($sop);
    }

    public function restore(AuthUser $authUser, Sop $sop): bool
    {
        return $authUser->can("Restore:Sop")
            && $this->ownsRecordCompany($sop);
    }

    public function forceDelete(AuthUser $authUser, Sop $sop): bool
    {
        return $authUser->can("ForceDelete:Sop")
            && $this->ownsRecordCompany($sop);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can("ForceDeleteAny:Sop");
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can("RestoreAny:Sop");
    }

    public function replicate(AuthUser $authUser, Sop $sop): bool
    {
        return $authUser->can("Replicate:Sop")
            && $this->ownsRecordCompany($sop);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can("Reorder:Sop");
    }
}
