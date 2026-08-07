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
        // Pemilik paket (punya permission) boleh create dalam kuota; SA juga.
        return $authUser->can('Create:User');
    }

    public function update(AuthUser $authUser, User $user): bool
    {
        return $authUser->can('Update:User')
            && UserVisibility::canAccessUser($user);
    }

    public function delete(AuthUser $authUser, User $user): bool
    {
        if (! $authUser->can('Delete:User') || ! UserVisibility::canAccessUser($user)) {
            return false;
        }

        // Jangan hapus diri sendiri / root tim kecuali SA
        if (UserVisibility::actorIsSuperAdmin()) {
            return true;
        }

        return (int) $user->id !== UserVisibility::teamRootId();
    }

    public function restore(AuthUser $authUser, User $user): bool
    {
        return $authUser->can('Restore:User')
            && UserVisibility::canAccessUser($user);
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

    public function replicate(AuthUser $authUser, User $user): bool
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
