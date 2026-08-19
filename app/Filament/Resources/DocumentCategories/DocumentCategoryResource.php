<?php

namespace App\Filament\Resources\DocumentCategories;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\DocumentCategories\Pages\CreateDocumentCategory;
use App\Filament\Resources\DocumentCategories\Pages\EditDocumentCategory;
use App\Filament\Resources\DocumentCategories\Pages\ListDocumentCategories;
use App\Filament\Resources\DocumentCategories\Schemas\DocumentCategoryForm;
use App\Filament\Resources\DocumentCategories\Tables\DocumentCategoriesTable;
use App\Models\DocumentCategory;
use App\Support\ProFeatures;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DocumentCategoryResource extends BaseResource
{
    protected static ?string $model = DocumentCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|\UnitEnum|null $navigationGroup = 'Administrasi';

    protected static ?string $recordTitleAttribute = 'name';

    protected static bool $isGloballySearchable = false;

    public static function form(Schema $schema): Schema
    {
        return DocumentCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DocumentCategoriesTable::configure($table);
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
            'index' => ListDocumentCategories::route('/'),
            'create' => CreateDocumentCategory::route('/create'),
            'edit' => EditDocumentCategory::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['parent:id,name'])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
