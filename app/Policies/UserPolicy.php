<?php

namespace App\Policies;

use App\Models\User;
use App\Support\UserVisibility;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:User');
    }

    public function view(AuthUser $authUser, User $user): bool
    {
        return $authUser->can('View:User')
            && UserVisibility::canAccessUser($user);
    }

    public function create(AuthUser $authUser): bool
    {
        if (! $authUser->can('Create:User')) {
            return false;
        }

        return UserVisibility::canCreateTeamUser();
    }

    public function update(AuthUser $authUser, User $user): bool
    {
        return $authUser->can('Update:User')
            && UserVisibility::canEditUser($user);
    }

    public function delete(AuthUser $authUser, User $user): bool
    {
        if (! $authUser->can('Delete:User')) {
            return false;
        }

        if (UserVisibility::actorIsSuperAdmin()) {
            return true;
        }

        // Paket 1 seat / non-owner: tidak hapus orang lain (diri sendiri tetap via SoftDelete jika diizinkan permission)
        return UserVisibility::canEditUser($user)
            && ! UserVisibility::isSingleSeatPlan()
            && (int) $authUser->id !== (int) $user->id;
    }

    public function restore(AuthUser $authUser, User $user): bool
    {
        return $authUser->can('Restore:User')
            && UserVisibility::actorIsSuperAdmin();
    }

    public function forceDelete(AuthUser $authUser, User $user): bool
    {
        return $authUser->can('ForceDelete:User')
            && UserVisibility::actorIsSuperAdmin();
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:User')
            && UserVisibility::actorIsSuperAdmin();
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:User')
            && UserVisibility::actorIsSuperAdmin();
    }

    public function replicate(AuthUser $authUser): bool
    {
        return $authUser->can('Replicate:User')
            && UserVisibility::actorIsSuperAdmin();
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:User')
            && UserVisibility::actorIsSuperAdmin();
    }
}
