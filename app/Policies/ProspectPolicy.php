<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Prospect;
use App\Policies\Concerns\ChecksCompanyOwnership;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ProspectPolicy
{
    use ChecksCompanyOwnership;
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can("ViewAny:Prospect");
    }

    public function view(AuthUser $authUser, Prospect $prospect): bool
    {
        return $authUser->can("View:Prospect")
            && $this->ownsRecordCompany($prospect);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can("Create:Prospect");
    }

    public function update(AuthUser $authUser, Prospect $prospect): bool
    {
        return $authUser->can("Update:Prospect")
            && $this->ownsRecordCompany($prospect);
    }

    public function delete(AuthUser $authUser, Prospect $prospect): bool
    {
        return $authUser->can("Delete:Prospect")
            && $this->ownsRecordCompany($prospect);
    }

    public function restore(AuthUser $authUser, Prospect $prospect): bool
    {
        return $authUser->can("Restore:Prospect")
            && $this->ownsRecordCompany($prospect);
    }

    public function forceDelete(AuthUser $authUser, Prospect $prospect): bool
    {
        return $authUser->can("ForceDelete:Prospect")
            && $this->ownsRecordCompany($prospect);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can("ForceDeleteAny:Prospect");
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can("RestoreAny:Prospect");
    }

    public function replicate(AuthUser $authUser, Prospect $prospect): bool
    {
        return $authUser->can("Replicate:Prospect")
            && $this->ownsRecordCompany($prospect);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can("Reorder:Prospect");
    }
}
