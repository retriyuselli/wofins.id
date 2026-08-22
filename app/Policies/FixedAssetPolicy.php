<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\FixedAsset;
use App\Policies\Concerns\ChecksCompanyOwnership;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class FixedAssetPolicy
{
    use ChecksCompanyOwnership;
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can("ViewAny:FixedAsset");
    }

    public function view(AuthUser $authUser, FixedAsset $fixedAsset): bool
    {
        return $authUser->can("View:FixedAsset")
            && $this->ownsRecordCompany($fixedAsset);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can("Create:FixedAsset");
    }

    public function update(AuthUser $authUser, FixedAsset $fixedAsset): bool
    {
        return $authUser->can("Update:FixedAsset")
            && $this->ownsRecordCompany($fixedAsset);
    }

    public function delete(AuthUser $authUser, FixedAsset $fixedAsset): bool
    {
        return $authUser->can("Delete:FixedAsset")
            && $this->ownsRecordCompany($fixedAsset);
    }

    public function restore(AuthUser $authUser, FixedAsset $fixedAsset): bool
    {
        return $authUser->can("Restore:FixedAsset")
            && $this->ownsRecordCompany($fixedAsset);
    }

    public function forceDelete(AuthUser $authUser, FixedAsset $fixedAsset): bool
    {
        return $authUser->can("ForceDelete:FixedAsset")
            && $this->ownsRecordCompany($fixedAsset);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can("ForceDeleteAny:FixedAsset");
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can("RestoreAny:FixedAsset");
    }

    public function replicate(AuthUser $authUser, FixedAsset $fixedAsset): bool
    {
        return $authUser->can("Replicate:FixedAsset")
            && $this->ownsRecordCompany($fixedAsset);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can("Reorder:FixedAsset");
    }
}
