<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SopCategory;
use App\Policies\Concerns\ChecksCompanyOwnership;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class SopCategoryPolicy
{
    use ChecksCompanyOwnership;
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can("ViewAny:SopCategory");
    }

    public function view(AuthUser $authUser, SopCategory $sopCategory): bool
    {
        return $authUser->can("View:SopCategory")
            && $this->ownsRecordCompany($sopCategory);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can("Create:SopCategory");
    }

    public function update(AuthUser $authUser, SopCategory $sopCategory): bool
    {
        return $authUser->can("Update:SopCategory")
            && $this->ownsRecordCompany($sopCategory);
    }

    public function delete(AuthUser $authUser, SopCategory $sopCategory): bool
    {
        return $authUser->can("Delete:SopCategory")
            && $this->ownsRecordCompany($sopCategory);
    }

    public function restore(AuthUser $authUser, SopCategory $sopCategory): bool
    {
        return $authUser->can("Restore:SopCategory")
            && $this->ownsRecordCompany($sopCategory);
    }

    public function forceDelete(AuthUser $authUser, SopCategory $sopCategory): bool
    {
        return $authUser->can("ForceDelete:SopCategory")
            && $this->ownsRecordCompany($sopCategory);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can("ForceDeleteAny:SopCategory");
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can("RestoreAny:SopCategory");
    }

    public function replicate(AuthUser $authUser, SopCategory $sopCategory): bool
    {
        return $authUser->can("Replicate:SopCategory")
            && $this->ownsRecordCompany($sopCategory);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can("Reorder:SopCategory");
    }
}
