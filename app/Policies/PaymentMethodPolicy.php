<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PaymentMethod;
use App\Policies\Concerns\ChecksCompanyOwnership;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class PaymentMethodPolicy
{
    use ChecksCompanyOwnership;
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can("ViewAny:PaymentMethod");
    }

    public function view(AuthUser $authUser, PaymentMethod $paymentMethod): bool
    {
        return $authUser->can("View:PaymentMethod")
            && $this->ownsRecordCompany($paymentMethod);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can("Create:PaymentMethod");
    }

    public function update(AuthUser $authUser, PaymentMethod $paymentMethod): bool
    {
        return $authUser->can("Update:PaymentMethod")
            && $this->ownsRecordCompany($paymentMethod);
    }

    public function delete(AuthUser $authUser, PaymentMethod $paymentMethod): bool
    {
        return $authUser->can("Delete:PaymentMethod")
            && $this->ownsRecordCompany($paymentMethod);
    }

    public function restore(AuthUser $authUser, PaymentMethod $paymentMethod): bool
    {
        return $authUser->can("Restore:PaymentMethod")
            && $this->ownsRecordCompany($paymentMethod);
    }

    public function forceDelete(AuthUser $authUser, PaymentMethod $paymentMethod): bool
    {
        return $authUser->can("ForceDelete:PaymentMethod")
            && $this->ownsRecordCompany($paymentMethod);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can("ForceDeleteAny:PaymentMethod");
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can("RestoreAny:PaymentMethod");
    }

    public function replicate(AuthUser $authUser, PaymentMethod $paymentMethod): bool
    {
        return $authUser->can("Replicate:PaymentMethod")
            && $this->ownsRecordCompany($paymentMethod);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can("Reorder:PaymentMethod");
    }
}
