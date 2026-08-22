<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Piutang;
use App\Policies\Concerns\ChecksCompanyOwnership;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class PiutangPolicy
{
    use ChecksCompanyOwnership;
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can("ViewAny:Piutang");
    }

    public function view(AuthUser $authUser, Piutang $piutang): bool
    {
        return $authUser->can("View:Piutang")
            && $this->ownsRecordCompany($piutang);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can("Create:Piutang");
    }

    public function update(AuthUser $authUser, Piutang $piutang): bool
    {
        return $authUser->can("Update:Piutang")
            && $this->ownsRecordCompany($piutang);
    }

    public function delete(AuthUser $authUser, Piutang $piutang): bool
    {
        return $authUser->can("Delete:Piutang")
            && $this->ownsRecordCompany($piutang);
    }

    public function restore(AuthUser $authUser, Piutang $piutang): bool
    {
        return $authUser->can("Restore:Piutang")
            && $this->ownsRecordCompany($piutang);
    }

    public function forceDelete(AuthUser $authUser, Piutang $piutang): bool
    {
        return $authUser->can("ForceDelete:Piutang")
            && $this->ownsRecordCompany($piutang);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can("ForceDeleteAny:Piutang");
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can("RestoreAny:Piutang");
    }

    public function replicate(AuthUser $authUser, Piutang $piutang): bool
    {
        return $authUser->can("Replicate:Piutang")
            && $this->ownsRecordCompany($piutang);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can("Reorder:Piutang");
    }
}
