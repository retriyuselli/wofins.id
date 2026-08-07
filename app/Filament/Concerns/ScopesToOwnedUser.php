<?php

namespace App\Filament\Concerns;

use App\Support\UserVisibility;
use Illuminate\Database\Eloquent\Builder;

/**
 * Scope resource ke record milik auth user (kecuali super_admin).
 */
trait ScopesToOwnedUser
{
    /**
     * Nama kolom pemilik. Override di resource bila beda.
     */
    protected static function ownerUserColumn(): string
    {
        return 'user_id';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        return UserVisibility::constrainOwnedQuery($query, static::ownerUserColumn());
    }
}
