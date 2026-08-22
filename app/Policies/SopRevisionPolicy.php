<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SopRevision;
use App\Policies\Concerns\ChecksCompanyOwnership;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class SopRevisionPolicy
{
    use ChecksCompanyOwnership;
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_sop::revision')
            || $authUser->can('ViewAny:SopRevision');
    }

    public function view(AuthUser $authUser, SopRevision $sopRevision): bool
    {
        return ($authUser->can('view_sop::revision') || $authUser->can('View:SopRevision'))
            && $this->ownsRecordCompany($sopRevision);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_sop::revision')
            || $authUser->can('Create:SopRevision');
    }

    public function update(AuthUser $authUser, SopRevision $sopRevision): bool
    {
        return ($authUser->can('update_sop::revision') || $authUser->can('Update:SopRevision'))
            && $this->ownsRecordCompany($sopRevision);
    }

    public function delete(AuthUser $authUser, SopRevision $sopRevision): bool
    {
        return ($authUser->can('delete_sop::revision') || $authUser->can('Delete:SopRevision'))
            && $this->ownsRecordCompany($sopRevision);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('delete_any_sop::revision')
            || $authUser->can('DeleteAny:SopRevision');
    }

    public function forceDelete(AuthUser $authUser, SopRevision $sopRevision): bool
    {
        return ($authUser->can('force_delete_sop::revision') || $authUser->can('ForceDelete:SopRevision'))
            && $this->ownsRecordCompany($sopRevision);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_sop::revision')
            || $authUser->can('ForceDeleteAny:SopRevision');
    }

    public function restore(AuthUser $authUser, SopRevision $sopRevision): bool
    {
        return ($authUser->can('restore_sop::revision') || $authUser->can('Restore:SopRevision'))
            && $this->ownsRecordCompany($sopRevision);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_sop::revision')
            || $authUser->can('RestoreAny:SopRevision');
    }

    public function replicate(AuthUser $authUser, SopRevision $sopRevision): bool
    {
        return ($authUser->can('replicate_sop::revision') || $authUser->can('Replicate:SopRevision'))
            && $this->ownsRecordCompany($sopRevision);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_sop::revision')
            || $authUser->can('Reorder:SopRevision');
    }
}
