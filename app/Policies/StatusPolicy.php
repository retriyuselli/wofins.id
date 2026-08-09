<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Status;
use App\Support\ProFeatures;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class StatusPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Status');
    }

    public function view(AuthUser $authUser, Status $status): bool
    {
        return $authUser->can('View:Status');
    }

    public function create(AuthUser $authUser): bool
    {
        return ProFeatures::actorIsSuperAdmin() && $authUser->can('Create:Status');
    }

    public function update(AuthUser $authUser, Status $status): bool
    {
        return ProFeatures::actorIsSuperAdmin() && $authUser->can('Update:Status');
    }

    public function delete(AuthUser $authUser, Status $status): bool
    {
        return ProFeatures::actorIsSuperAdmin() && $authUser->can('Delete:Status');
    }

    public function restore(AuthUser $authUser, Status $status): bool
    {
        return ProFeatures::actorIsSuperAdmin() && $authUser->can('Restore:Status');
    }

    public function forceDelete(AuthUser $authUser, Status $status): bool
    {
        return ProFeatures::actorIsSuperAdmin() && $authUser->can('ForceDelete:Status');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return ProFeatures::actorIsSuperAdmin() && $authUser->can('ForceDeleteAny:Status');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return ProFeatures::actorIsSuperAdmin() && $authUser->can('RestoreAny:Status');
    }

    public function replicate(AuthUser $authUser, Status $status): bool
    {
        return ProFeatures::actorIsSuperAdmin() && $authUser->can('Replicate:Status');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return ProFeatures::actorIsSuperAdmin() && $authUser->can('Reorder:Status');
    }
}