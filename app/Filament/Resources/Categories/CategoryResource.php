<?php

namespace App\Filament\Resources\Categories;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Filament\Resources\Categories\Schemas\CategoryForm;
use App\Filament\Resources\Categories\Tables\CategoriesTable;
use App\Models\Category;
use App\Support\CompanySubscription;
use App\Support\UserVisibility;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CategoryResource extends BaseResource
{
    protected static ?string $model = Category::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static string|\UnitEnum|null $navigationGroup = 'Penjualan';

    protected static ?string $navigationLabel = 'Kategori';

    public static function form(Schema $schema): Schema
    {
        return CategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CategoriesTable::configure($table);
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
            'index' => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'edit' => EditCategory::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return UserVisibility::constrainCompanyQuery(parent::getEloquentQuery());
    }

    public static function getNavigationBadge(): ?string
    {
        return CompanySubscription::navigationBadge(CompanySubscription::RESOURCE_CATEGORIES);
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return CompanySubscription::summary(CompanySubscription::RESOURCE_CATEGORIES);
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return CompanySubscription::canCreate(CompanySubscription::RESOURCE_CATEGORIES) ? 'primary' : 'warning';
    }
}
