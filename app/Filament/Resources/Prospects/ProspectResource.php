<?php

namespace App\Filament\Resources\Prospects;

use App\Filament\Resources\Prospects\Pages\CreateProspect;
use App\Filament\Resources\Prospects\Pages\EditProspect;
use App\Filament\Resources\Prospects\Pages\ListProspects;
use App\Filament\Resources\Prospects\Pages\ProspectsThisMonth;
use App\Filament\Resources\Prospects\Pages\ProspectsThisWeek;
use App\Filament\Resources\Prospects\Pages\ViewProspect;
use App\Filament\Resources\Prospects\Schemas\ProspectForm;
use App\Filament\Resources\Prospects\Tables\ProspectsTable;
use App\Models\Prospect;
use App\Filament\Resources\BaseResource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Cache;

class ProspectResource extends BaseResource
{
    protected static ?string $model = Prospect::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Penjualan';

    protected static ?string $navigationLabel = 'Prospek';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return ProspectForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProspectsTable::configure($table);
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
            'index' => ListProspects::route('/'),
            'create' => CreateProspect::route('/create'),
            'edit' => EditProspect::route('/{record}/edit'),
            'view' => ViewProspect::route('/view'),
            'this-month' => ProspectsThisMonth::route('/bulan-ini'),
            'this-week' => ProspectsThisWeek::route('/minggu-ini'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return \App\Support\UserVisibility::constrainOwnedQuery(
            parent::getEloquentQuery()
                ->with(['user:id,name', 'latestOrder'])
                ->withoutGlobalScopes([
                    SoftDeletingScope::class,
                ])
        );
    }

    private static function getCachedNavigationBadgeCount(): int
    {
        $scope = \App\Support\UserVisibility::cacheScopeKey();

        return Cache::remember(
            "nav:prospects:without_orders:{$scope}",
            60,
            fn (): int => (int) static::getEloquentQuery()->whereDoesntHave('orders')->count()
        );
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getCachedNavigationBadgeCount();
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Calon client yang terdaftar';
    }
}
