<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Order;
use App\Policies\Concerns\ChecksCompanyOwnership;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class OrderPolicy
{
    use ChecksCompanyOwnership;
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can("ViewAny:Order");
    }

    public function view(AuthUser $authUser, Order $order): bool
    {
        return $authUser->can("View:Order")
            && $this->ownsRecordCompany($order);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can("Create:Order");
    }

    public function update(AuthUser $authUser, Order $order): bool
    {
        return $authUser->can("Update:Order")
            && $this->ownsRecordCompany($order);
    }

    public function delete(AuthUser $authUser, Order $order): bool
    {
        return $authUser->can("Delete:Order")
            && $this->ownsRecordCompany($order);
    }

    public function restore(AuthUser $authUser, Order $order): bool
    {
        return $authUser->can("Restore:Order")
            && $this->ownsRecordCompany($order);
    }

    public function forceDelete(AuthUser $authUser, Order $order): bool
    {
        return $authUser->can("ForceDelete:Order")
            && $this->ownsRecordCompany($order);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can("ForceDeleteAny:Order");
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can("RestoreAny:Order");
    }

    public function replicate(AuthUser $authUser, Order $order): bool
    {
        return $authUser->can("Replicate:Order")
            && $this->ownsRecordCompany($order);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can("Reorder:Order");
    }
}
