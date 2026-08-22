<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PengeluaranLain;
use App\Policies\Concerns\ChecksCompanyOwnership;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class PengeluaranLainPolicy
{
    use ChecksCompanyOwnership;
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can("ViewAny:PengeluaranLain");
    }

    public function view(AuthUser $authUser, PengeluaranLain $pengeluaranLain): bool
    {
        return $authUser->can("View:PengeluaranLain")
            && $this->ownsRecordCompany($pengeluaranLain);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can("Create:PengeluaranLain");
    }

    public function update(AuthUser $authUser, PengeluaranLain $pengeluaranLain): bool
    {
        return $authUser->can("Update:PengeluaranLain")
            && $this->ownsRecordCompany($pengeluaranLain);
    }

    public function delete(AuthUser $authUser, PengeluaranLain $pengeluaranLain): bool
    {
        return $authUser->can("Delete:PengeluaranLain")
            && $this->ownsRecordCompany($pengeluaranLain);
    }

    public function restore(AuthUser $authUser, PengeluaranLain $pengeluaranLain): bool
    {
        return $authUser->can("Restore:PengeluaranLain")
            && $this->ownsRecordCompany($pengeluaranLain);
    }

    public function forceDelete(AuthUser $authUser, PengeluaranLain $pengeluaranLain): bool
    {
        return $authUser->can("ForceDelete:PengeluaranLain")
            && $this->ownsRecordCompany($pengeluaranLain);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can("ForceDeleteAny:PengeluaranLain");
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can("RestoreAny:PengeluaranLain");
    }

    public function replicate(AuthUser $authUser, PengeluaranLain $pengeluaranLain): bool
    {
        return $authUser->can("Replicate:PengeluaranLain")
            && $this->ownsRecordCompany($pengeluaranLain);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can("Reorder:PengeluaranLain");
    }
}
