<?php

namespace App\Filament\Resources\Statuses;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\Statuses\Pages\CreateStatus;
use App\Filament\Resources\Statuses\Pages\EditStatus;
use App\Filament\Resources\Statuses\Pages\ListStatuses;
use App\Filament\Resources\Statuses\Schemas\StatusForm;
use App\Filament\Resources\Statuses\Tables\StatusesTable;
use App\Models\Status;
use App\Support\ProFeatures;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class StatusResource extends BaseResource
{
    protected static ?string $model = Status::class;

    protected static string|\UnitEnum|null $navigationGroup = 'SDM';

    protected static ?string $navigationLabel = 'Status Jabatan (Login)';

    protected static ?string $modelLabel = 'status jabatan';

    protected static ?string $pluralModelLabel = 'status jabatan';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-check-badge';

    public static function form(Schema $schema): Schema
    {
        return StatusForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StatusesTable::configure($table);
    }

    public static function canViewAny(): bool
    {
        return ProFeatures::actorIsSuperAdmin() && parent::canViewAny();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return ProFeatures::actorIsSuperAdmin() && parent::shouldRegisterNavigation();
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
            'index' => ListStatuses::route('/'),
            'create' => CreateStatus::route('/create'),
            'edit' => EditStatus::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
