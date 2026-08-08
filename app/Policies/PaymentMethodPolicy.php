<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PaymentMethod;
use App\Models\User;
use App\Support\ProFeatures;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class PaymentMethodPolicy
{
    use HandlesAuthorization;

    private function owns(AuthUser $authUser, PaymentMethod $paymentMethod): bool
    {
        if (ProFeatures::actorIsSuperAdmin()) {
            return true;
        }

        if (! $authUser instanceof User || ! $authUser->company_id) {
            return false;
        }

        return (int) $paymentMethod->company_id === (int) $authUser->company_id;
    }

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PaymentMethod');
    }

    public function view(AuthUser $authUser, PaymentMethod $paymentMethod): bool
    {
        return $authUser->can('View:PaymentMethod') && $this->owns($authUser, $paymentMethod);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PaymentMethod');
    }

    public function update(AuthUser $authUser, PaymentMethod $paymentMethod): bool
    {
        return $authUser->can('Update:PaymentMethod') && $this->owns($authUser, $paymentMethod);
    }

    public function delete(AuthUser $authUser, PaymentMethod $paymentMethod): bool
    {
        return $authUser->can('Delete:PaymentMethod') && $this->owns($authUser, $paymentMethod);
    }

    public function restore(AuthUser $authUser, PaymentMethod $paymentMethod): bool
    {
        return $authUser->can('Restore:PaymentMethod') && $this->owns($authUser, $paymentMethod);
    }

    public function forceDelete(AuthUser $authUser, PaymentMethod $paymentMethod): bool
    {
        return ProFeatures::actorIsSuperAdmin() && $authUser->can('ForceDelete:PaymentMethod');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return ProFeatures::actorIsSuperAdmin() && $authUser->can('ForceDeleteAny:PaymentMethod');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return ProFeatures::actorIsSuperAdmin() && $authUser->can('RestoreAny:PaymentMethod');
    }

    public function replicate(AuthUser $authUser, PaymentMethod $paymentMethod): bool
    {
        return $authUser->can('Replicate:PaymentMethod') && $this->owns($authUser, $paymentMethod);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:PaymentMethod');
    }
}
