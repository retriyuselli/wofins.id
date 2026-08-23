<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SimulasiProduk;
use App\Policies\Concerns\ChecksCompanyOwnership;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class SimulasiProdukPolicy
{
    use ChecksCompanyOwnership;
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can("ViewAny:SimulasiProduk");
    }

    public function view(AuthUser $authUser, SimulasiProduk $simulasiProduk): bool
    {
        return $authUser->can("View:SimulasiProduk")
            && $this->ownsRecordCompany($simulasiProduk);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can("Create:SimulasiProduk");
    }

    public function update(AuthUser $authUser, SimulasiProduk $simulasiProduk): bool
    {
        return $authUser->can("Update:SimulasiProduk")
            && $this->ownsRecordCompany($simulasiProduk);
    }

    public function delete(AuthUser $authUser, SimulasiProduk $simulasiProduk): bool
    {
        return $this->canManageOwnSimulasi($authUser)
            && $this->ownsRecordCompany($simulasiProduk);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->canManageOwnSimulasi($authUser);
    }

    public function restore(AuthUser $authUser, SimulasiProduk $simulasiProduk): bool
    {
        return $this->canManageOwnSimulasi($authUser)
            && $this->ownsRecordCompany($simulasiProduk);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->canManageOwnSimulasi($authUser);
    }

    public function forceDelete(AuthUser $authUser, SimulasiProduk $simulasiProduk): bool
    {
        return $this->canManageOwnSimulasi($authUser)
            && $this->ownsRecordCompany($simulasiProduk);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->canManageOwnSimulasi($authUser);
    }

    private function canManageOwnSimulasi(AuthUser $authUser): bool
    {
        return $authUser->can('Delete:SimulasiProduk')
            || $authUser->can('Update:SimulasiProduk')
            || $authUser->can('ViewAny:SimulasiProduk')
            || $authUser->can('ForceDelete:SimulasiProduk');
    }

    public function replicate(AuthUser $authUser, SimulasiProduk $simulasiProduk): bool
    {
        return $authUser->can("Replicate:SimulasiProduk")
            && $this->ownsRecordCompany($simulasiProduk);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can("Reorder:SimulasiProduk");
    }
}
