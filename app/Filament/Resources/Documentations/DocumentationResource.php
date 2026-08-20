<?php

namespace App\Filament\Resources\Documentations;

use App\Filament\Resources\Documentations\Pages;
use App\Filament\Resources\Documentations\Schemas\DocumentationForm;
use App\Filament\Resources\Documentations\Tables\DocumentationsTable;
use App\Models\Documentation;
use App\Filament\Resources\BaseResource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;
use BackedEnum;

class DocumentationResource extends BaseResource
{
    protected static ?string $model = Documentation::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|UnitEnum|null $navigationGroup = 'Knowledge Base';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return DocumentationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DocumentationsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return \App\Support\UserVisibility::constrainCompanyQuery(parent::getEloquentQuery());
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDocumentations::route('/'),
            'create' => Pages\CreateDocumentation::route('/create'),
            'edit' => Pages\EditDocumentation::route('/{record}/edit'),
        ];
    }
}
