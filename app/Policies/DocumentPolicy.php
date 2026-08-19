<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Document;
use App\Support\UserVisibility;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class DocumentPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Document');
    }

    public function view(AuthUser $authUser, Document $document): bool
    {
        return $authUser->can('View:Document')
            && UserVisibility::ownsCompanyId($document->company_id !== null ? (int) $document->company_id : null);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Document');
    }

    public function update(AuthUser $authUser, Document $document): bool
    {
        return $authUser->can('Update:Document')
            && UserVisibility::ownsCompanyId($document->company_id !== null ? (int) $document->company_id : null);
    }

    public function delete(AuthUser $authUser, Document $document): bool
    {
        return $authUser->can('Delete:Document')
            && UserVisibility::ownsCompanyId($document->company_id !== null ? (int) $document->company_id : null);
    }

    public function restore(AuthUser $authUser, Document $document): bool
    {
        return $authUser->can('Restore:Document')
            && UserVisibility::ownsCompanyId($document->company_id !== null ? (int) $document->company_id : null);
    }

    public function forceDelete(AuthUser $authUser, Document $document): bool
    {
        return $authUser->can('ForceDelete:Document')
            && UserVisibility::ownsCompanyId($document->company_id !== null ? (int) $document->company_id : null);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Document');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Document');
    }

    public function replicate(AuthUser $authUser, Document $document): bool
    {
        return $authUser->can('Replicate:Document')
            && UserVisibility::ownsCompanyId($document->company_id !== null ? (int) $document->company_id : null);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Document');
    }

}