<?php

namespace App\Filament\Resources\Categories;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Filament\Resources\Categories\Schemas\CategoryForm;
use App\Filament\Resources\Categories\Tables\CategoriesTable;
use App\Models\Category;
use App\Support\ProFeatures;
use App\Support\UserVisibility;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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

    public static function canCreate(): bool
    {
        return ProFeatures::actorIsSuperAdmin() && parent::canCreate();
    }

    public static function canEdit(Model $record): bool
    {
        return ProFeatures::actorIsSuperAdmin() && parent::canEdit($record);
    }

    public static function canDelete(Model $record): bool
    {
        return ProFeatures::actorIsSuperAdmin() && parent::canDelete($record);
    }

    public static function canDeleteAny(): bool
    {
        return ProFeatures::actorIsSuperAdmin() && parent::canDeleteAny();
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
        return (string) static::getEloquentQuery()->count();
    }
}
