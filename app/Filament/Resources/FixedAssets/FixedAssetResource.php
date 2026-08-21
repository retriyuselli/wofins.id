<?php

namespace App\Filament\Resources\FixedAssets;

use App\Filament\Resources\FixedAssets\Pages\CreateFixedAsset;
use App\Filament\Resources\FixedAssets\Pages\DepreciationHistory;
use App\Filament\Resources\FixedAssets\Pages\EditFixedAsset;
use App\Filament\Resources\FixedAssets\Pages\ListFixedAssets;
use App\Filament\Resources\FixedAssets\Pages\ViewFixedAsset;
use App\Filament\Resources\FixedAssets\Schemas\FixedAssetForm;
use App\Filament\Resources\FixedAssets\Tables\FixedAssetsTable;
use App\Filament\Resources\BaseResource;
use App\Models\FixedAsset;
use App\Support\CompanySubscription;
use App\Support\UserVisibility;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FixedAssetResource extends BaseResource
{
    protected static ?string $model = FixedAsset::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Aset Tetap';

    protected static string|\UnitEnum|null $navigationGroup = 'Keuangan';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return FixedAssetForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FixedAssetsTable::configure($table);
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
            'index' => ListFixedAssets::route('/'),
            'create' => CreateFixedAsset::route('/create'),
            'view' => ViewFixedAsset::route('/{record}'),
            'edit' => EditFixedAsset::route('/{record}/edit'),
            'depreciation-history' => DepreciationHistory::route('/{record}/depreciation-history'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return UserVisibility::constrainCompanyQuery(
            parent::getEloquentQuery()->with(['company:id,company_name'])
        );
    }

    public static function getNavigationBadge(): ?string
    {
        return CompanySubscription::navigationBadge(CompanySubscription::RESOURCE_FIXED_ASSETS);
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return CompanySubscription::summary(CompanySubscription::RESOURCE_FIXED_ASSETS);
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return CompanySubscription::canCreate(CompanySubscription::RESOURCE_FIXED_ASSETS) ? 'primary' : 'warning';
    }
}
