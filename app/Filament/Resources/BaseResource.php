<?php

namespace App\Filament\Resources;

use App\Support\PlanResourceGate;
use Filament\Resources\Resource;

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
}
