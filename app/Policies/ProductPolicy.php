<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Product;
use App\Policies\Concerns\ChecksCompanyOwnership;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ProductPolicy
{
    use ChecksCompanyOwnership;
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can("ViewAny:Product");
    }

    public function view(AuthUser $authUser, Product $product): bool
    {
        return $authUser->can("View:Product")
            && $this->ownsRecordCompany($product);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can("Create:Product");
    }

    public function update(AuthUser $authUser, Product $product): bool
    {
        return $authUser->can("Update:Product")
            && $this->ownsRecordCompany($product);
    }

    public function delete(AuthUser $authUser, Product $product): bool
    {
        return $authUser->can("Delete:Product")
            && $this->ownsRecordCompany($product);
    }

    public function restore(AuthUser $authUser, Product $product): bool
    {
        return $authUser->can("Restore:Product")
            && $this->ownsRecordCompany($product);
    }

    public function forceDelete(AuthUser $authUser, Product $product): bool
    {
        return $authUser->can("ForceDelete:Product")
            && $this->ownsRecordCompany($product);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can("ForceDeleteAny:Product");
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can("RestoreAny:Product");
    }

    public function replicate(AuthUser $authUser, Product $product): bool
    {
        return $authUser->can("Replicate:Product")
            && $this->ownsRecordCompany($product);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can("Reorder:Product");
    }
}
