<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\NotaDinas;
use App\Policies\Concerns\ChecksCompanyOwnership;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class NotaDinasPolicy
{
    use ChecksCompanyOwnership;
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can("ViewAny:NotaDinas");
    }

    public function view(AuthUser $authUser, NotaDinas $notaDinas): bool
    {
        return $authUser->can("View:NotaDinas")
            && $this->ownsRecordCompany($notaDinas);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can("Create:NotaDinas");
    }

    public function update(AuthUser $authUser, NotaDinas $notaDinas): bool
    {
        return $authUser->can("Update:NotaDinas")
            && $this->ownsRecordCompany($notaDinas);
    }

    public function delete(AuthUser $authUser, NotaDinas $notaDinas): bool
    {
        return $authUser->can("Delete:NotaDinas")
            && $this->ownsRecordCompany($notaDinas);
    }

    public function restore(AuthUser $authUser, NotaDinas $notaDinas): bool
    {
        return $authUser->can("Restore:NotaDinas")
            && $this->ownsRecordCompany($notaDinas);
    }

    public function forceDelete(AuthUser $authUser, NotaDinas $notaDinas): bool
    {
        return $authUser->can("ForceDelete:NotaDinas")
            && $this->ownsRecordCompany($notaDinas);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can("ForceDeleteAny:NotaDinas");
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can("RestoreAny:NotaDinas");
    }

    public function replicate(AuthUser $authUser, NotaDinas $notaDinas): bool
    {
        return $authUser->can("Replicate:NotaDinas")
            && $this->ownsRecordCompany($notaDinas);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can("Reorder:NotaDinas");
    }
}
