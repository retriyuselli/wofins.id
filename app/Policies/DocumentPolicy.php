<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Document;
use App\Policies\Concerns\ChecksCompanyOwnership;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class DocumentPolicy
{
    use ChecksCompanyOwnership;
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can("ViewAny:Document");
    }

    public function view(AuthUser $authUser, Document $document): bool
    {
        return $authUser->can("View:Document")
            && $this->ownsRecordCompany($document);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can("Create:Document");
    }

    public function update(AuthUser $authUser, Document $document): bool
    {
        return $authUser->can("Update:Document")
            && $this->ownsRecordCompany($document);
    }

    public function delete(AuthUser $authUser, Document $document): bool
    {
        return $authUser->can("Delete:Document")
            && $this->ownsRecordCompany($document);
    }

    public function restore(AuthUser $authUser, Document $document): bool
    {
        return $authUser->can("Restore:Document")
            && $this->ownsRecordCompany($document);
    }

    public function forceDelete(AuthUser $authUser, Document $document): bool
    {
        return $authUser->can("ForceDelete:Document")
            && $this->ownsRecordCompany($document);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can("ForceDeleteAny:Document");
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can("RestoreAny:Document");
    }

    public function replicate(AuthUser $authUser, Document $document): bool
    {
        return $authUser->can("Replicate:Document")
            && $this->ownsRecordCompany($document);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can("Reorder:Document");
    }
}
