<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PendapatanLain;
use App\Policies\Concerns\ChecksCompanyOwnership;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class PendapatanLainPolicy
{
    use ChecksCompanyOwnership;
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can("ViewAny:PendapatanLain");
    }

    public function view(AuthUser $authUser, PendapatanLain $pendapatanLain): bool
    {
        return $authUser->can("View:PendapatanLain")
            && $this->ownsRecordCompany($pendapatanLain);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can("Create:PendapatanLain");
    }

    public function update(AuthUser $authUser, PendapatanLain $pendapatanLain): bool
    {
        return $authUser->can("Update:PendapatanLain")
            && $this->ownsRecordCompany($pendapatanLain);
    }

    public function delete(AuthUser $authUser, PendapatanLain $pendapatanLain): bool
    {
        return $authUser->can("Delete:PendapatanLain")
            && $this->ownsRecordCompany($pendapatanLain);
    }

    public function restore(AuthUser $authUser, PendapatanLain $pendapatanLain): bool
    {
        return $authUser->can("Restore:PendapatanLain")
            && $this->ownsRecordCompany($pendapatanLain);
    }

    public function forceDelete(AuthUser $authUser, PendapatanLain $pendapatanLain): bool
    {
        return $authUser->can("ForceDelete:PendapatanLain")
            && $this->ownsRecordCompany($pendapatanLain);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can("ForceDeleteAny:PendapatanLain");
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can("RestoreAny:PendapatanLain");
    }

    public function replicate(AuthUser $authUser, PendapatanLain $pendapatanLain): bool
    {
        return $authUser->can("Replicate:PendapatanLain")
            && $this->ownsRecordCompany($pendapatanLain);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can("Reorder:PendapatanLain");
    }
}
