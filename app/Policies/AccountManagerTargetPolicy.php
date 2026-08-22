<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AccountManagerTarget;
use App\Policies\Concerns\ChecksCompanyOwnership;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class AccountManagerTargetPolicy
{
    use ChecksCompanyOwnership;
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can("ViewAny:AccountManagerTarget");
    }

    public function view(AuthUser $authUser, AccountManagerTarget $accountManagerTarget): bool
    {
        return $authUser->can("View:AccountManagerTarget")
            && $this->ownsRecordCompany($accountManagerTarget);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can("Create:AccountManagerTarget");
    }

    public function update(AuthUser $authUser, AccountManagerTarget $accountManagerTarget): bool
    {
        return $authUser->can("Update:AccountManagerTarget")
            && $this->ownsRecordCompany($accountManagerTarget);
    }

    public function delete(AuthUser $authUser, AccountManagerTarget $accountManagerTarget): bool
    {
        return $authUser->can("Delete:AccountManagerTarget")
            && $this->ownsRecordCompany($accountManagerTarget);
    }

    public function restore(AuthUser $authUser, AccountManagerTarget $accountManagerTarget): bool
    {
        return $authUser->can("Restore:AccountManagerTarget")
            && $this->ownsRecordCompany($accountManagerTarget);
    }

    public function forceDelete(AuthUser $authUser, AccountManagerTarget $accountManagerTarget): bool
    {
        return $authUser->can("ForceDelete:AccountManagerTarget")
            && $this->ownsRecordCompany($accountManagerTarget);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can("ForceDeleteAny:AccountManagerTarget");
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can("RestoreAny:AccountManagerTarget");
    }

    public function replicate(AuthUser $authUser, AccountManagerTarget $accountManagerTarget): bool
    {
        return $authUser->can("Replicate:AccountManagerTarget")
            && $this->ownsRecordCompany($accountManagerTarget);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can("Reorder:AccountManagerTarget");
    }
}
