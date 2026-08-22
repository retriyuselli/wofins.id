<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Vendor;
use App\Policies\Concerns\ChecksCompanyOwnership;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class VendorPolicy
{
    use ChecksCompanyOwnership;
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can("ViewAny:Vendor");
    }

    public function view(AuthUser $authUser, Vendor $vendor): bool
    {
        return $authUser->can("View:Vendor")
            && $this->ownsRecordCompany($vendor);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can("Create:Vendor");
    }

    public function update(AuthUser $authUser, Vendor $vendor): bool
    {
        return $authUser->can("Update:Vendor")
            && $this->ownsRecordCompany($vendor);
    }

    public function delete(AuthUser $authUser, Vendor $vendor): bool
    {
        return $authUser->can("Delete:Vendor")
            && $this->ownsRecordCompany($vendor);
    }

    public function restore(AuthUser $authUser, Vendor $vendor): bool
    {
        return $authUser->can("Restore:Vendor")
            && $this->ownsRecordCompany($vendor);
    }

    public function forceDelete(AuthUser $authUser, Vendor $vendor): bool
    {
        return $authUser->can("ForceDelete:Vendor")
            && $this->ownsRecordCompany($vendor);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can("ForceDeleteAny:Vendor");
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can("RestoreAny:Vendor");
    }

    public function replicate(AuthUser $authUser, Vendor $vendor): bool
    {
        return $authUser->can("Replicate:Vendor")
            && $this->ownsRecordCompany($vendor);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can("Reorder:Vendor");
    }
}
