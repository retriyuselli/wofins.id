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

    protected static string|\UnitEnum|null $navigationGroup = 'SDM';

    protected static ?string $navigationLabel = 'Data Tim';

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
            ]);

        // Tabel tidak punya user_id — non–super_admin tidak melihat data tim orang lain.
        if (! \App\Support\UserVisibility::actorIsSuperAdmin()) {
            return $query->whereRaw('1 = 0');
        }

        return $query;
    }

    public static function getNavigationBadge(): ?string
    {
        if (! \App\Support\UserVisibility::actorIsSuperAdmin()) {
            return null;
        }

        return (string) static::getEloquentQuery()->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'primary';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Data crew freelance';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['nama_lengkap', 'email', 'nomor_telepon', 'pekerjaan'];
    }
}
