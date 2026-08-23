<?php

namespace App\Filament\Resources;

use App\Support\CompanyRecordAuthorization;
use App\Support\PlanResourceGate;
use App\Support\ProFeatures;
use App\Support\UserVisibility;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

/**
 * Base Filament resource: menu & akses digating paket company (bukan role Spatie starter/pro).
 *
 * Override canViewAny (bukan canAccess): di Filament, canAccess() = canViewAny(),
 * jadi override canAccess + parent::canAccess() mudah membuat infinite loop
 * jika resource juga override canViewAny → canAccess.
 */
abstract class BaseResource extends Resource
{
    public static function canViewAny(): bool
    {
        if (! PlanResourceGate::allowsAccessTo(static::class)) {
            return false;
        }

        return parent::canViewAny();
    }

    public static function shouldRegisterNavigation(): bool
    {
        if (! PlanResourceGate::allowsAccessTo(static::class)) {
            return false;
        }

        return parent::shouldRegisterNavigation();
    }

    public static function canDeleteAny(): bool
    {
        return static::canViewAny();
    }

    public static function canRestoreAny(): bool
    {
        return static::canViewAny();
    }

    public static function canForceDeleteAny(): bool
    {
        return static::canViewAny();
    }

    public static function canDelete(Model $record): bool
    {
        return static::companyOwnsOperationalRecord($record) || parent::canDelete($record);
    }

    public static function canRestore(Model $record): bool
    {
        return static::companyOwnsOperationalRecord($record) || parent::canRestore($record);
    }

    public static function canForceDelete(Model $record): bool
    {
        return static::companyOwnsOperationalRecord($record) || parent::canForceDelete($record);
    }

    protected static function companyOwnsOperationalRecord(Model $record): bool
    {
        if (ProFeatures::actorIsSuperAdmin()) {
            return true;
        }

        if (in_array($record::class, CompanyRecordAuthorization::excludedModels(), true)) {
            return false;
        }

        if (! isset($record->company_id)) {
            return false;
        }

        $companyId = $record->company_id;

        return UserVisibility::ownsCompanyId($companyId !== null ? (int) $companyId : null);
    }
}
