<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Expense;
use App\Policies\Concerns\ChecksCompanyOwnership;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ExpensePolicy
{
    use ChecksCompanyOwnership;
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can("ViewAny:Expense");
    }

    public function view(AuthUser $authUser, Expense $expense): bool
    {
        return $authUser->can("View:Expense")
            && $this->ownsRecordCompany($expense);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can("Create:Expense");
    }

    public function update(AuthUser $authUser, Expense $expense): bool
    {
        return $authUser->can("Update:Expense")
            && $this->ownsRecordCompany($expense);
    }

    public function delete(AuthUser $authUser, Expense $expense): bool
    {
        return $authUser->can("Delete:Expense")
            && $this->ownsRecordCompany($expense);
    }

    public function restore(AuthUser $authUser, Expense $expense): bool
    {
        return $authUser->can("Restore:Expense")
            && $this->ownsRecordCompany($expense);
    }

    public function forceDelete(AuthUser $authUser, Expense $expense): bool
    {
        return $authUser->can("ForceDelete:Expense")
            && $this->ownsRecordCompany($expense);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can("ForceDeleteAny:Expense");
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can("RestoreAny:Expense");
    }

    public function replicate(AuthUser $authUser, Expense $expense): bool
    {
        return $authUser->can("Replicate:Expense")
            && $this->ownsRecordCompany($expense);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can("Reorder:Expense");
    }
}
