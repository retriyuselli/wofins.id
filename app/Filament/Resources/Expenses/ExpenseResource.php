<?php

namespace App\Filament\Resources\Expenses;

use App\Filament\Resources\Expenses\Pages\CreateExpense;
use App\Filament\Resources\Expenses\Pages\ListExpenses;
use App\Filament\Resources\Expenses\Schemas\ExpenseForm;
use App\Filament\Resources\Expenses\Tables\ExpensesTable;
use App\Filament\Resources\Expenses\Widgets\ExpenseOverview;
use App\Filament\Resources\BaseResource;
use App\Models\Expense;
use App\Support\CompanySubscription;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ExpenseResource extends BaseResource
{
    protected static ?string $model = Expense::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-receipt-refund';

    protected static ?string $navigationLabel = 'Pengeluaran Wedding';

    protected static string|\UnitEnum|null $navigationGroup = 'Kas Proyek';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return ExpenseForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ExpensesTable::configure($table);
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
            'index' => ListExpenses::route('/'),
            'create' => CreateExpense::route('/create'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            ExpenseOverview::class,
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Kas Proyek';
    }

    public static function getNavigationBadge(): ?string
    {
        return CompanySubscription::navigationBadge(CompanySubscription::RESOURCE_EXPENSES);
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return CompanySubscription::canCreate(CompanySubscription::RESOURCE_EXPENSES) ? 'primary' : 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return CompanySubscription::summary(CompanySubscription::RESOURCE_EXPENSES);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return \App\Support\UserVisibility::constrainCompanyQuery(
            parent::getEloquentQuery()
                ->with([
                    'order.prospect:id,name_event',
                    'vendor:id,name',
                    'paymentMethod:id,bank_name,name,no_rekening',
                ])
        );
    }
}
