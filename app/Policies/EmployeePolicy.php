<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Employee;
use App\Policies\Concerns\ChecksCompanyOwnership;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class EmployeePolicy
{
    use ChecksCompanyOwnership;
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can("ViewAny:Employee");
    }

    public function view(AuthUser $authUser, Employee $employee): bool
    {
        return $authUser->can("View:Employee")
            && $this->ownsRecordCompany($employee);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can("Create:Employee");
    }

    public function update(AuthUser $authUser, Employee $employee): bool
    {
        return $authUser->can("Update:Employee")
            && $this->ownsRecordCompany($employee);
    }

    public function delete(AuthUser $authUser, Employee $employee): bool
    {
        return $authUser->can("Delete:Employee")
            && $this->ownsRecordCompany($employee);
    }

    public function restore(AuthUser $authUser, Employee $employee): bool
    {
        return $authUser->can("Restore:Employee")
            && $this->ownsRecordCompany($employee);
    }

    public function forceDelete(AuthUser $authUser, Employee $employee): bool
    {
        return $authUser->can("ForceDelete:Employee")
            && $this->ownsRecordCompany($employee);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can("ForceDeleteAny:Employee");
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can("RestoreAny:Employee");
    }

    public function replicate(AuthUser $authUser, Employee $employee): bool
    {
        return $authUser->can("Replicate:Employee")
            && $this->ownsRecordCompany($employee);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can("Reorder:Employee");
    }
}
