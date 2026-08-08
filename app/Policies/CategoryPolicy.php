<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Category;
use App\Models\User;
use App\Support\ProFeatures;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class CategoryPolicy
{
    use HandlesAuthorization;

    private function isPlatformAdmin(AuthUser $authUser): bool
    {
        return $authUser instanceof User
            && $authUser->hasAnyRole(['super_admin', 'admin']);
    }

    private function owns(AuthUser $authUser, Category $category): bool
    {
        if (ProFeatures::actorIsSuperAdmin()) {
            return true;
        }

        if (! $authUser instanceof User || ! $authUser->company_id) {
            return false;
        }

        return (int) $category->company_id === (int) $authUser->company_id;
    }

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Category');
    }

    public function view(AuthUser $authUser, Category $category): bool
    {
        return $authUser->can('View:Category') && $this->owns($authUser, $category);
    }

    public function create(AuthUser $authUser): bool
    {
        return $this->isPlatformAdmin($authUser) || $authUser->can('Create:Category');
    }

    public function update(AuthUser $authUser, Category $category): bool
    {
        return ($this->isPlatformAdmin($authUser) || $authUser->can('Update:Category'))
            && $this->owns($authUser, $category);
    }

    public function delete(AuthUser $authUser, Category $category): bool
    {
        return ($this->isPlatformAdmin($authUser) || $authUser->can('Delete:Category'))
            && $this->owns($authUser, $category);
    }

    public function restore(AuthUser $authUser, Category $category): bool
    {
        return ($this->isPlatformAdmin($authUser) || $authUser->can('Restore:Category'))
            && $this->owns($authUser, $category);
    }

    public function forceDelete(AuthUser $authUser, Category $category): bool
    {
        return ProFeatures::actorIsSuperAdmin() && $authUser->can('ForceDelete:Category');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return ProFeatures::actorIsSuperAdmin() && $authUser->can('ForceDeleteAny:Category');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return ProFeatures::actorIsSuperAdmin() && $authUser->can('RestoreAny:Category');
    }

    public function replicate(AuthUser $authUser, Category $category): bool
    {
        return ($this->isPlatformAdmin($authUser) || $authUser->can('Replicate:Category'))
            && $this->owns($authUser, $category);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $this->isPlatformAdmin($authUser) || $authUser->can('Reorder:Category');
    }
}
