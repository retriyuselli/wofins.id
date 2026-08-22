<?php

namespace App\Filament\Resources\DataPribadis;

use App\Filament\Resources\DataPribadis\Pages\CreateDataPribadi;
use App\Filament\Resources\DataPribadis\Pages\EditDataPribadi;
use App\Filament\Resources\DataPribadis\Pages\ListDataPribadis;
use App\Filament\Resources\DataPribadis\Schemas\DataPribadiForm;
use App\Filament\Resources\DataPribadis\Tables\DataPribadisTable;
use App\Models\DataPribadi;
use App\Filament\Resources\BaseResource;
use App\Support\ProFeatures;
use App\Support\UserVisibility;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DataPribadiResource extends BaseResource
{
    protected static ?string $model = DataPribadi::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-identification';

    protected static string|\UnitEnum|null $navigationGroup = 'Penjualan';

    protected static ?string $navigationLabel = 'Crew Freelance';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'crew freelance';

    protected static ?string $pluralModelLabel = 'crew freelance';

    protected static ?string $recordTitleAttribute = 'nama_lengkap';

    protected static bool $isGloballySearchable = false;

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function canViewAny(): bool
    {
        if (! \App\Support\PlanResourceGate::allowsAccessTo(static::class)) {
            return false;
        }

        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if (ProFeatures::actorIsSuperAdmin()) {
            return true;
        }

        if (UserVisibility::companyId()) {
            return $user->can('ViewAny:DataPribadi')
                || (method_exists($user, 'hasRole') && $user->hasRole('pengunjung'))
                || parent::canViewAny();
        }

        return parent::canViewAny();
    }

    public static function form(Schema $schema): Schema
    {
        return DataPribadiForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DataPribadisTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDataPribadis::route('/'),
            'create' => CreateDataPribadi::route('/create'),
            'edit' => EditDataPribadi::route('/{record}/edit'),
        ];
    }

    public static function canDeleteAny(): bool
    {
        return static::canViewAny();
    }

    public static function canDelete(Model $record): bool
    {
        return static::companyUserOwnsCrew($record);
    }

    public static function canRestore(Model $record): bool
    {
        return static::companyUserOwnsCrew($record);
    }

    public static function canRestoreAny(): bool
    {
        return static::canViewAny();
    }

    public static function canForceDelete(Model $record): bool
    {
        return static::companyUserOwnsCrew($record);
    }

    public static function canForceDeleteAny(): bool
    {
        return static::canViewAny();
    }

    private static function companyUserOwnsCrew(Model $record): bool
    {
        if (! $record instanceof DataPribadi) {
            return false;
        }

        if (ProFeatures::actorIsSuperAdmin()) {
            return true;
        }

        $companyId = $record->company_id;

        return UserVisibility::ownsCompanyId($companyId !== null ? (int) $companyId : null);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->with(['company:id,company_name']);

        return \App\Support\UserVisibility::constrainCompanyQuery($query);
    }

    public static function getNavigationBadge(): ?string
    {
        try {
            return (string) static::getEloquentQuery()->count();
        } catch (\Throwable) {
            return null;
        }
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'primary';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Data crew freelance company (bukan data pribadi akun user)';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['nama_lengkap', 'email', 'nomor_telepon', 'pekerjaan'];
    }
}
