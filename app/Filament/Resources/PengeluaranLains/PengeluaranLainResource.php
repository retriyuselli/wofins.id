<?php

namespace App\Filament\Resources\PengeluaranLains;

use App\Filament\Resources\PengeluaranLains\Pages\CreatePengeluaranLain;
use App\Filament\Resources\PengeluaranLains\Pages\EditPengeluaranLain;
use App\Filament\Resources\PengeluaranLains\Pages\ListPengeluaranLains;
use App\Filament\Resources\PengeluaranLains\Schemas\PengeluaranLainForm;
use App\Filament\Resources\PengeluaranLains\Tables\PengeluaranLainsTable;
use App\Filament\Resources\PengeluaranLains\Widgets\PengeluaranOverviewWidgets;
use App\Filament\Resources\BaseResource;
use App\Models\PengeluaranLain;
use App\Support\CompanySubscription;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class PengeluaranLainResource extends BaseResource
{
    protected static ?string $model = PengeluaranLain::class;

    protected static ?string $navigationLabel = 'Pengeluaran Lain';

    protected static string|\UnitEnum|null $navigationGroup = 'Kas Operasional';

    protected static ?int $navigationSort = 3;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-trending-down';

    /**
     * Safely convert any value to float for calculations
     */
    private static function safeFloatVal($value): float
    {
        if (is_null($value)) {
            return 0.0;
        }

        if (is_numeric($value)) {
            return floatval($value);
        }

        if (is_string($value)) {
            // Remove any non-numeric characters except dots and commas
            $cleaned = preg_replace('/[^\d.,]/', '', $value);
            // Remove commas (thousand separators)
            $cleaned = str_replace(',', '', $cleaned);
            // Handle empty string after cleaning
            if ($cleaned === '' || $cleaned === '.') {
                return 0.0;
            }

            return floatval($cleaned);
        }

        if (is_array($value)) {
            // If somehow we get an array, return 0
            return 0.0;
        }

        // Fallback for any other data type
        return 0.0;
    }

    public static function form(Schema $schema): Schema
    {
        return PengeluaranLainForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PengeluaranLainsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Kas Operasional';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPengeluaranLains::route('/'),
            'create' => CreatePengeluaranLain::route('/create'),
            'edit' => EditPengeluaranLain::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return CompanySubscription::navigationBadge(CompanySubscription::RESOURCE_PENGELUARAN_LAINS);
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return CompanySubscription::canCreate(CompanySubscription::RESOURCE_PENGELUARAN_LAINS) ? 'primary' : 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return CompanySubscription::summary(CompanySubscription::RESOURCE_PENGELUARAN_LAINS);
    }

    public static function getWidgets(): array
    {
        return [
            PengeluaranOverviewWidgets::class,
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return \App\Support\UserVisibility::constrainCompanyQuery(
            parent::getEloquentQuery()
                ->with([
                    'vendor:id,name',
                    'paymentMethod:id,name,no_rekening',
                    'notaDinas:id,status',
                ])
        );
    }
}
