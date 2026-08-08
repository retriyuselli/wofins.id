<?php

namespace App\Filament\Resources\Companies;

use App\Filament\Resources\Companies\Pages\CreateCompany;
use App\Filament\Resources\Companies\Pages\EditCompany;
use App\Filament\Resources\Companies\Pages\ListCompanies;
use App\Filament\Resources\Companies\Schemas\CompanyForm;
use App\Filament\Resources\Companies\Tables\CompaniesTable;
use App\Models\Company;
use App\Support\ProFeatures;
use App\Support\UserVisibility;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home-modern';

    protected static string|\UnitEnum|null $navigationGroup = 'Administrasi';

    protected static ?string $navigationLabel = 'Perusahaan';

    protected static ?string $modelLabel = 'Perusahaan';

    protected static ?string $pluralModelLabel = 'Perusahaan';

    public static function form(Schema $schema): Schema
    {
        return CompanyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CompaniesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (ProFeatures::actorIsSuperAdmin()) {
            return $query;
        }

        $companyId = UserVisibility::companyId();

        if ($companyId === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereKey($companyId);
    }

    public static function canCreate(): bool
    {
        return ProFeatures::actorIsSuperAdmin() && parent::canCreate();
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCompanies::route('/'),
            'create' => CreateCompany::route('/create'),
            'edit' => EditCompany::route('/{record}/edit'),
        ];
    }
}
