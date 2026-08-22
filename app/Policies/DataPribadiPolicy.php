<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DataPribadi;
use App\Policies\Concerns\ChecksCompanyOwnership;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class DataPribadiPolicy
{
    use ChecksCompanyOwnership;
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can("ViewAny:DataPribadi");
    }

    public function view(AuthUser $authUser, DataPribadi $dataPribadi): bool
    {
        return $authUser->can("View:DataPribadi")
            && $this->ownsRecordCompany($dataPribadi);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can("Create:DataPribadi");
    }

    public function update(AuthUser $authUser, DataPribadi $dataPribadi): bool
    {
        return $authUser->can("Update:DataPribadi")
            && $this->ownsRecordCompany($dataPribadi);
    }

    public function delete(AuthUser $authUser, DataPribadi $dataPribadi): bool
    {
        return $this->canManageOwnCrew($authUser)
            && $this->ownsRecordCompany($dataPribadi);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->canManageOwnCrew($authUser);
    }

    public function restore(AuthUser $authUser, DataPribadi $dataPribadi): bool
    {
        return $this->canManageOwnCrew($authUser)
            && $this->ownsRecordCompany($dataPribadi);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->canManageOwnCrew($authUser);
    }

    public function forceDelete(AuthUser $authUser, DataPribadi $dataPribadi): bool
    {
        return $authUser->can('ForceDelete:DataPribadi')
            && $this->ownsRecordCompany($dataPribadi);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:DataPribadi');
    }

    /**
     * Company user yang bisa membuka modul crew boleh hapus/pulihkan datanya sendiri.
     */
    private function canManageOwnCrew(AuthUser $authUser): bool
    {
        return $authUser->can('Delete:DataPribadi')
            || $authUser->can('Update:DataPribadi')
            || $authUser->can('ViewAny:DataPribadi');
    }

    public function replicate(AuthUser $authUser, DataPribadi $dataPribadi): bool
    {
        return $authUser->can("Replicate:DataPribadi")
            && $this->ownsRecordCompany($dataPribadi);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can("Reorder:DataPribadi");
    }
}
