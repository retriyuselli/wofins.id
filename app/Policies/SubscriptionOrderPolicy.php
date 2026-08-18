<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\SubscriptionOrder;
use Illuminate\Auth\Access\HandlesAuthorization;

class SubscriptionOrderPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SubscriptionOrder');
    }

    public function view(AuthUser $authUser, SubscriptionOrder $subscriptionOrder): bool
    {
        return $authUser->can('View:SubscriptionOrder');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SubscriptionOrder');
    }

    public function update(AuthUser $authUser, SubscriptionOrder $subscriptionOrder): bool
    {
        return $authUser->can('Update:SubscriptionOrder');
    }

    public function delete(AuthUser $authUser, SubscriptionOrder $subscriptionOrder): bool
    {
        return $authUser->can('Delete:SubscriptionOrder');
    }

    public function restore(AuthUser $authUser, SubscriptionOrder $subscriptionOrder): bool
    {
        return $authUser->can('Restore:SubscriptionOrder');
    }

    public function forceDelete(AuthUser $authUser, SubscriptionOrder $subscriptionOrder): bool
    {
        return $authUser->can('ForceDelete:SubscriptionOrder');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SubscriptionOrder');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SubscriptionOrder');
    }

    public function replicate(AuthUser $authUser, SubscriptionOrder $subscriptionOrder): bool
    {
        return $authUser->can('Replicate:SubscriptionOrder');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SubscriptionOrder');
    }

}