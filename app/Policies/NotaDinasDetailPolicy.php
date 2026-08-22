<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\NotaDinasDetail;
use App\Policies\Concerns\ChecksCompanyOwnership;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class NotaDinasDetailPolicy
{
    use ChecksCompanyOwnership;
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can("ViewAny:NotaDinasDetail");
    }

    public function view(AuthUser $authUser, NotaDinasDetail $notaDinasDetail): bool
    {
        return $authUser->can("View:NotaDinasDetail")
            && $this->ownsRecordCompany($notaDinasDetail);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can("Create:NotaDinasDetail");
    }

    public function update(AuthUser $authUser, NotaDinasDetail $notaDinasDetail): bool
    {
        return $authUser->can("Update:NotaDinasDetail")
            && $this->ownsRecordCompany($notaDinasDetail);
    }

    public function delete(AuthUser $authUser, NotaDinasDetail $notaDinasDetail): bool
    {
        return $authUser->can("Delete:NotaDinasDetail")
            && $this->ownsRecordCompany($notaDinasDetail);
    }

    public function restore(AuthUser $authUser, NotaDinasDetail $notaDinasDetail): bool
    {
        return $authUser->can("Restore:NotaDinasDetail")
            && $this->ownsRecordCompany($notaDinasDetail);
    }

    public function forceDelete(AuthUser $authUser, NotaDinasDetail $notaDinasDetail): bool
    {
        return $authUser->can("ForceDelete:NotaDinasDetail")
            && $this->ownsRecordCompany($notaDinasDetail);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can("ForceDeleteAny:NotaDinasDetail");
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can("RestoreAny:NotaDinasDetail");
    }

    public function replicate(AuthUser $authUser, NotaDinasDetail $notaDinasDetail): bool
    {
        return $authUser->can("Replicate:NotaDinasDetail")
            && $this->ownsRecordCompany($notaDinasDetail);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can("Reorder:NotaDinasDetail");
    }
}
