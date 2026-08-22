<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ExpenseOps;
use App\Policies\Concerns\ChecksCompanyOwnership;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ExpenseOpsPolicy
{
    use ChecksCompanyOwnership;
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can("ViewAny:ExpenseOps");
    }

    public function view(AuthUser $authUser, ExpenseOps $expenseOps): bool
    {
        return $authUser->can("View:ExpenseOps")
            && $this->ownsRecordCompany($expenseOps);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can("Create:ExpenseOps");
    }

    public function update(AuthUser $authUser, ExpenseOps $expenseOps): bool
    {
        return $authUser->can("Update:ExpenseOps")
            && $this->ownsRecordCompany($expenseOps);
    }

    public function delete(AuthUser $authUser, ExpenseOps $expenseOps): bool
    {
        return $authUser->can("Delete:ExpenseOps")
            && $this->ownsRecordCompany($expenseOps);
    }

    public function restore(AuthUser $authUser, ExpenseOps $expenseOps): bool
    {
        return $authUser->can("Restore:ExpenseOps")
            && $this->ownsRecordCompany($expenseOps);
    }

    public function forceDelete(AuthUser $authUser, ExpenseOps $expenseOps): bool
    {
        return $authUser->can("ForceDelete:ExpenseOps")
            && $this->ownsRecordCompany($expenseOps);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can("ForceDeleteAny:ExpenseOps");
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can("RestoreAny:ExpenseOps");
    }

    public function replicate(AuthUser $authUser, ExpenseOps $expenseOps): bool
    {
        return $authUser->can("Replicate:ExpenseOps")
            && $this->ownsRecordCompany($expenseOps);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can("Reorder:ExpenseOps");
    }
}
