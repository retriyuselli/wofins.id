<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PembayaranPiutang;
use App\Policies\Concerns\ChecksCompanyOwnership;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class PembayaranPiutangPolicy
{
    use ChecksCompanyOwnership;
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can("ViewAny:PembayaranPiutang");
    }

    public function view(AuthUser $authUser, PembayaranPiutang $pembayaranPiutang): bool
    {
        return $authUser->can("View:PembayaranPiutang")
            && $this->ownsRecordCompany($pembayaranPiutang);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can("Create:PembayaranPiutang");
    }

    public function update(AuthUser $authUser, PembayaranPiutang $pembayaranPiutang): bool
    {
        return $authUser->can("Update:PembayaranPiutang")
            && $this->ownsRecordCompany($pembayaranPiutang);
    }

    public function delete(AuthUser $authUser, PembayaranPiutang $pembayaranPiutang): bool
    {
        return $authUser->can("Delete:PembayaranPiutang")
            && $this->ownsRecordCompany($pembayaranPiutang);
    }

    public function restore(AuthUser $authUser, PembayaranPiutang $pembayaranPiutang): bool
    {
        return $authUser->can("Restore:PembayaranPiutang")
            && $this->ownsRecordCompany($pembayaranPiutang);
    }

    public function forceDelete(AuthUser $authUser, PembayaranPiutang $pembayaranPiutang): bool
    {
        return $authUser->can("ForceDelete:PembayaranPiutang")
            && $this->ownsRecordCompany($pembayaranPiutang);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can("ForceDeleteAny:PembayaranPiutang");
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can("RestoreAny:PembayaranPiutang");
    }

    public function replicate(AuthUser $authUser, PembayaranPiutang $pembayaranPiutang): bool
    {
        return $authUser->can("Replicate:PembayaranPiutang")
            && $this->ownsRecordCompany($pembayaranPiutang);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can("Reorder:PembayaranPiutang");
    }
}
