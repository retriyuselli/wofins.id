<?php

namespace App\Filament\Resources\Expenses\Pages;

use App\Filament\Actions\GenerateWeddingExpensesAction;
use App\Filament\Resources\Expenses\ExpenseResource;
use App\Filament\Resources\Expenses\Widgets\ExpenseOverview;
use App\Support\CompanySubscription;
use App\Support\UserVisibility;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListExpenses extends ListRecords
{
    protected static string $resource = ExpenseResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [
            GenerateWeddingExpensesAction::make(),
        ];

        if (UserVisibility::canViewTeamSeatSummary()) {
            $actions[] = Action::make('quota_pengeluaran_wedding')
                ->label(CompanySubscription::summary(CompanySubscription::RESOURCE_EXPENSES))
                ->icon('heroicon-o-receipt-refund')
                ->color(CompanySubscription::canCreate(CompanySubscription::RESOURCE_EXPENSES) ? 'gray' : 'warning')
                ->disabled()
                ->extraAttributes(['class' => 'pointer-events-none']);
        }

        return $actions;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ExpenseOverview::class,
        ];
    }
}
