<?php

namespace App\Filament\Resources\DataPribadis;

use App\Filament\Resources\DataPribadis\Pages\CreateDataPribadi;
use App\Filament\Resources\DataPribadis\Pages\EditDataPribadi;
use App\Filament\Resources\DataPribadis\Pages\ListDataPribadis;
use App\Filament\Resources\DataPribadis\Schemas\DataPribadiForm;
use App\Filament\Resources\DataPribadis\Tables\DataPribadisTable;
use App\Models\DataPribadi;
use App\Filament\Resources\BaseResource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DataPribadiResource extends BaseResource
{
    protected static ?string $model = DataPribadi::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-identification';

    protected static string|\UnitEnum|null $navigationGroup = 'Penjualan';

    protected static ?string $navigationLabel = 'Crew Freelance';

    protected static ?string $modelLabel = 'crew freelance';

    protected static ?string $pluralModelLabel = 'crew freelance';

    protected static ?string $recordTitleAttribute = 'nama_lengkap';

    protected static bool $isGloballySearchable = false;

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

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->with(['company']);

        return \App\Support\UserVisibility::constrainCompanyQuery($query);
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getEloquentQuery()->count();
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
