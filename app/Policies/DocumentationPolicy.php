<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Documentation;
use App\Policies\Concerns\ChecksCompanyOwnership;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class DocumentationPolicy
{
    use ChecksCompanyOwnership;
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can("ViewAny:Documentation");
    }

    public function view(AuthUser $authUser, Documentation $documentation): bool
    {
        return $authUser->can("View:Documentation")
            && $this->ownsRecordCompany($documentation);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can("Create:Documentation");
    }

    public function update(AuthUser $authUser, Documentation $documentation): bool
    {
        return $authUser->can("Update:Documentation")
            && $this->ownsRecordCompany($documentation);
    }

    public function delete(AuthUser $authUser, Documentation $documentation): bool
    {
        return $authUser->can("Delete:Documentation")
            && $this->ownsRecordCompany($documentation);
    }

    public function restore(AuthUser $authUser, Documentation $documentation): bool
    {
        return $authUser->can("Restore:Documentation")
            && $this->ownsRecordCompany($documentation);
    }

    public function forceDelete(AuthUser $authUser, Documentation $documentation): bool
    {
        return $authUser->can("ForceDelete:Documentation")
            && $this->ownsRecordCompany($documentation);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can("ForceDeleteAny:Documentation");
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can("RestoreAny:Documentation");
    }

    public function replicate(AuthUser $authUser, Documentation $documentation): bool
    {
        return $authUser->can("Replicate:Documentation")
            && $this->ownsRecordCompany($documentation);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can("Reorder:Documentation");
    }
}
