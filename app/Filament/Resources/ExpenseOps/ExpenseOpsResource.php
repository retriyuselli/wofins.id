<?php

namespace App\Filament\Resources\ExpenseOps;

use App\Filament\Resources\ExpenseOps\Pages\CreateExpenseOps;
use App\Filament\Resources\ExpenseOps\Pages\EditExpenseOps;
use App\Filament\Resources\ExpenseOps\Pages\ListExpenseOps;
use App\Filament\Resources\ExpenseOps\Schemas\ExpenseOpForm;
use App\Filament\Resources\ExpenseOps\Tables\ExpenseOpsTable;
use App\Filament\Resources\ExpenseOps\Widgets\ExpenseOpsOverview;
use App\Filament\Resources\BaseResource;
use App\Models\ExpenseOps;
use App\Support\CompanySubscription;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ExpenseOpsResource extends BaseResource
{
    protected static ?string $model = ExpenseOps::class;

    protected static ?string $navigationLabel = 'Pengeluaran Operasional';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-8-tooth';

    protected static string|\UnitEnum|null $navigationGroup = 'Kas Operasional';

    protected static ?int $navigationSort = 1;

    /**
     * Safely convert any value to float for calculations
     */
    private static function safeFloatVal($value)
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
        return ExpenseOpForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ExpenseOpsTable::configure($table);
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
            'index' => ListExpenseOps::route('/'),
            'create' => CreateExpenseOps::route('/create'),
            'edit' => EditExpenseOps::route('/{record}/edit'),
        ];
    }

    public static function getWidgets(): array
    {
        return [ExpenseOpsOverview::class];
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Kas Operasional';
    }

    public static function getEloquentQuery(): Builder
    {
        return \App\Support\UserVisibility::constrainCompanyQuery(
            parent::getEloquentQuery()
                ->with([
                    'vendor:id,name',
                    'paymentMethod:id,name,no_rekening',
                    'notaDinas:id,status',
                    'company:id,company_name',
                ])
                ->withoutGlobalScopes([
                    SoftDeletingScope::class,
                ])
        );
    }

    public static function getNavigationBadge(): ?string
    {
        return CompanySubscription::navigationBadge(CompanySubscription::RESOURCE_EXPENSE_OPS);
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return CompanySubscription::canCreate(CompanySubscription::RESOURCE_EXPENSE_OPS) ? 'primary' : 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return CompanySubscription::summary(CompanySubscription::RESOURCE_EXPENSE_OPS);
    }
}
