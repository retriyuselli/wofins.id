<?php

namespace App\Filament\Resources\PendapatanLains;

use App\Filament\Resources\PendapatanLains\Pages\CreatePendapatanLain;
use App\Filament\Resources\PendapatanLains\Pages\EditPendapatanLain;
use App\Filament\Resources\PendapatanLains\Pages\ListPendapatanLains;
use App\Filament\Resources\PendapatanLains\Schemas\PendapatanLainForm;
use App\Filament\Resources\PendapatanLains\Tables\PendapatanLainsTable;
use App\Filament\Resources\PendapatanLains\Widgets\PendapatanLainOverviewWidget;
use App\Filament\Resources\BaseResource;
use App\Models\PendapatanLain;
use App\Support\CompanySubscription;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PendapatanLainResource extends BaseResource
{
    protected static ?string $model = PendapatanLain::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-trending-up';

    protected static ?string $recordTitleAttribute = 'name';

    protected static bool $isGloballySearchable = false;

    protected static ?string $navigationLabel = 'Pendapatan Lain';

    protected static string|\UnitEnum|null $navigationGroup = 'Kas Operasional';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return PendapatanLainForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PendapatanLainsTable::configure($table);
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Kas Operasional';
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
            'index' => ListPendapatanLains::route('/'),
            'create' => CreatePendapatanLain::route('/create'),
            'edit' => EditPendapatanLain::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return \App\Support\UserVisibility::constrainCompanyQuery(
            parent::getEloquentQuery()
                ->with([
                    'vendor:id,name',
                    'paymentMethod:id,name',
                ])
                ->withoutGlobalScopes([
                    SoftDeletingScope::class,
                ])
        );
    }

    public static function getNavigationBadge(): ?string
    {
        return CompanySubscription::navigationBadge(CompanySubscription::RESOURCE_PENDAPATAN_LAINS);
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return CompanySubscription::canCreate(CompanySubscription::RESOURCE_PENDAPATAN_LAINS) ? 'primary' : 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return CompanySubscription::summary(CompanySubscription::RESOURCE_PENDAPATAN_LAINS);
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function getWidgets(): array
    {
        return [
            PendapatanLainOverviewWidget::class,
        ];
    }
}
