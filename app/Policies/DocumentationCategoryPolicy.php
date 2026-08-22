<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DocumentationCategory;
use App\Policies\Concerns\ChecksCompanyOwnership;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class DocumentationCategoryPolicy
{
    use ChecksCompanyOwnership;
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can("ViewAny:DocumentationCategory");
    }

    public function view(AuthUser $authUser, DocumentationCategory $documentationCategory): bool
    {
        return $authUser->can("View:DocumentationCategory")
            && $this->ownsRecordCompany($documentationCategory);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can("Create:DocumentationCategory");
    }

    public function update(AuthUser $authUser, DocumentationCategory $documentationCategory): bool
    {
        return $authUser->can("Update:DocumentationCategory")
            && $this->ownsRecordCompany($documentationCategory);
    }

    public function delete(AuthUser $authUser, DocumentationCategory $documentationCategory): bool
    {
        return $authUser->can("Delete:DocumentationCategory")
            && $this->ownsRecordCompany($documentationCategory);
    }

    public function restore(AuthUser $authUser, DocumentationCategory $documentationCategory): bool
    {
        return $authUser->can("Restore:DocumentationCategory")
            && $this->ownsRecordCompany($documentationCategory);
    }

    public function forceDelete(AuthUser $authUser, DocumentationCategory $documentationCategory): bool
    {
        return $authUser->can("ForceDelete:DocumentationCategory")
            && $this->ownsRecordCompany($documentationCategory);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can("ForceDeleteAny:DocumentationCategory");
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can("RestoreAny:DocumentationCategory");
    }

    public function replicate(AuthUser $authUser, DocumentationCategory $documentationCategory): bool
    {
        return $authUser->can("Replicate:DocumentationCategory")
            && $this->ownsRecordCompany($documentationCategory);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can("Reorder:DocumentationCategory");
    }
}
