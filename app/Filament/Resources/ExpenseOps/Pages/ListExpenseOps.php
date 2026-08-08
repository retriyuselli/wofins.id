<?php

namespace App\Filament\Resources\ExpenseOps\Pages;

use App\Filament\Resources\ExpenseOps\ExpenseOpsResource;
use App\Filament\Resources\ExpenseOps\Widgets\ExpenseOpsOverview;
use App\Support\CompanySubscription;
use App\Support\ProFeatures;
use App\Support\UserVisibility;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListExpenseOps extends ListRecords
{
    protected static string $resource = ExpenseOpsResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [];

        if (UserVisibility::canViewTeamSeatSummary()) {
            $actions[] = Action::make('quota_expense_ops')
                ->label(CompanySubscription::summary(CompanySubscription::RESOURCE_EXPENSE_OPS))
                ->icon('heroicon-o-cog-8-tooth')
                ->color(CompanySubscription::canCreate(CompanySubscription::RESOURCE_EXPENSE_OPS) ? 'gray' : 'warning')
                ->disabled()
                ->extraAttributes(['class' => 'pointer-events-none']);
        }

        $actions[] = CreateAction::make()
            ->label('Tambah Pengeluaran Operasional')
            ->icon('heroicon-o-plus')
            ->disabled(fn () => ! CompanySubscription::canCreate(CompanySubscription::RESOURCE_EXPENSE_OPS)
                && ! ProFeatures::actorIsSuperAdmin())
            ->tooltip(fn () => CompanySubscription::canCreate(CompanySubscription::RESOURCE_EXPENSE_OPS)
                || ProFeatures::actorIsSuperAdmin()
                ? null
                : CompanySubscription::fullMessage(CompanySubscription::RESOURCE_EXPENSE_OPS));

        return $actions;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ExpenseOpsOverview::class,
        ];
    }
}
