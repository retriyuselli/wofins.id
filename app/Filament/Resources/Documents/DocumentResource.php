<?php

namespace App\Filament\Resources\Documents;

use App\Filament\Resources\Documents\Pages\CreateDocument;
use App\Filament\Resources\Documents\Pages\EditDocument;
use App\Filament\Resources\Documents\Pages\ListDocuments;
use App\Filament\Resources\Documents\Pages\ViewDocument;
use App\Filament\Resources\Documents\Schemas\DocumentForm;
use App\Filament\Resources\Documents\Tables\DocumentsTable;
use App\Models\Document;
use BackedEnum;
use App\Filament\Resources\BaseResource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DocumentResource extends BaseResource
{
    protected static ?string $model = Document::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentArrowDown;

    protected static string|\UnitEnum|null $navigationGroup = 'Administrasi';

    protected static ?string $recordTitleAttribute = 'title';

    protected static bool $isGloballySearchable = false;

    public static function form(Schema $schema): Schema
    {
        return DocumentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DocumentsTable::configure($table);
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
            'index' => ListDocuments::route('/'),
            'create' => CreateDocument::route('/create'),
            'view' => ViewDocument::route('/{record}'),
            'edit' => EditDocument::route('/{record}/edit'),
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
        $query = parent::getEloquentQuery()
            ->with(['category:id,name']);

        if (\App\Support\UserVisibility::actorIsSuperAdmin()) {
            return $query;
        }

        $teamIds = \App\Support\UserVisibility::teamUserIds();
        $actorId = \App\Support\UserVisibility::actorId();

        if ($teamIds === [] && $actorId === null) {
            return $query->whereRaw('1 = 0');
        }

        // Dokumen dibuat anggota tim, atau actor adalah penerima
        return $query->where(function (Builder $q) use ($teamIds, $actorId) {
            if ($teamIds !== []) {
                $q->whereIn('created_by', $teamIds);
            }
            if ($actorId !== null) {
                $q->orWhereHas('recipientsList', fn (Builder $r) => $r->where('users.id', $actorId));
            }
        });
    }
}
