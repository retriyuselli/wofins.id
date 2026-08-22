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
        return $authUser->can("Delete:SimulasiProduk")
            && $this->ownsRecordCompany($simulasiProduk);
    }

    public function restore(AuthUser $authUser, SimulasiProduk $simulasiProduk): bool
    {
        return $authUser->can("Restore:SimulasiProduk")
            && $this->ownsRecordCompany($simulasiProduk);
    }

    public function forceDelete(AuthUser $authUser, SimulasiProduk $simulasiProduk): bool
    {
        return $authUser->can("ForceDelete:SimulasiProduk")
            && $this->ownsRecordCompany($simulasiProduk);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can("ForceDeleteAny:SimulasiProduk");
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can("RestoreAny:SimulasiProduk");
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
