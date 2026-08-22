<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\BankStatement;
use App\Policies\Concerns\ChecksCompanyOwnership;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class BankStatementPolicy
{
    use ChecksCompanyOwnership;
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can("ViewAny:BankStatement");
    }

    public function view(AuthUser $authUser, BankStatement $bankStatement): bool
    {
        return $authUser->can("View:BankStatement")
            && $this->ownsRecordCompany($bankStatement);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can("Create:BankStatement");
    }

    public function update(AuthUser $authUser, BankStatement $bankStatement): bool
    {
        return $authUser->can("Update:BankStatement")
            && $this->ownsRecordCompany($bankStatement);
    }

    public function delete(AuthUser $authUser, BankStatement $bankStatement): bool
    {
        return $authUser->can("Delete:BankStatement")
            && $this->ownsRecordCompany($bankStatement);
    }

    public function restore(AuthUser $authUser, BankStatement $bankStatement): bool
    {
        return $authUser->can("Restore:BankStatement")
            && $this->ownsRecordCompany($bankStatement);
    }

    public function forceDelete(AuthUser $authUser, BankStatement $bankStatement): bool
    {
        return $authUser->can("ForceDelete:BankStatement")
            && $this->ownsRecordCompany($bankStatement);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can("ForceDeleteAny:BankStatement");
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can("RestoreAny:BankStatement");
    }

    public function replicate(AuthUser $authUser, BankStatement $bankStatement): bool
    {
        return $authUser->can("Replicate:BankStatement")
            && $this->ownsRecordCompany($bankStatement);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can("Reorder:BankStatement");
    }
}
