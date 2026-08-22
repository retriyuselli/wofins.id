<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DataPembayaran;
use App\Policies\Concerns\ChecksCompanyOwnership;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class DataPembayaranPolicy
{
    use ChecksCompanyOwnership;
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can("ViewAny:DataPembayaran");
    }

    public function view(AuthUser $authUser, DataPembayaran $dataPembayaran): bool
    {
        return $authUser->can("View:DataPembayaran")
            && $this->ownsRecordCompany($dataPembayaran);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can("Create:DataPembayaran");
    }

    public function update(AuthUser $authUser, DataPembayaran $dataPembayaran): bool
    {
        return $authUser->can("Update:DataPembayaran")
            && $this->ownsRecordCompany($dataPembayaran);
    }

    public function delete(AuthUser $authUser, DataPembayaran $dataPembayaran): bool
    {
        return $authUser->can("Delete:DataPembayaran")
            && $this->ownsRecordCompany($dataPembayaran);
    }

    public function restore(AuthUser $authUser, DataPembayaran $dataPembayaran): bool
    {
        return $authUser->can("Restore:DataPembayaran")
            && $this->ownsRecordCompany($dataPembayaran);
    }

    public function forceDelete(AuthUser $authUser, DataPembayaran $dataPembayaran): bool
    {
        return $authUser->can("ForceDelete:DataPembayaran")
            && $this->ownsRecordCompany($dataPembayaran);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can("ForceDeleteAny:DataPembayaran");
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can("RestoreAny:DataPembayaran");
    }

    public function replicate(AuthUser $authUser, DataPembayaran $dataPembayaran): bool
    {
        return $authUser->can("Replicate:DataPembayaran")
            && $this->ownsRecordCompany($dataPembayaran);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can("Reorder:DataPembayaran");
    }
}
