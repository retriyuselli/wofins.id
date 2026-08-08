<?php

namespace App\Filament\Resources\PendapatanLains\Pages;

use App\Filament\Resources\PendapatanLains\PendapatanLainResource;
use App\Filament\Resources\PendapatanLains\Widgets\PendapatanLainOverviewWidget;
use App\Support\CompanySubscription;
use App\Support\ProFeatures;
use App\Support\UserVisibility;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPendapatanLains extends ListRecords
{
    protected static string $resource = PendapatanLainResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [];

        if (UserVisibility::canViewTeamSeatSummary()) {
            $actions[] = Action::make('quota_pendapatan_lain')
                ->label(CompanySubscription::summary(CompanySubscription::RESOURCE_PENDAPATAN_LAINS))
                ->icon('heroicon-o-arrow-trending-up')
                ->color(CompanySubscription::canCreate(CompanySubscription::RESOURCE_PENDAPATAN_LAINS) ? 'gray' : 'warning')
                ->disabled()
                ->extraAttributes(['class' => 'pointer-events-none']);
        }

        $actions[] = CreateAction::make()
            ->disabled(fn () => ! CompanySubscription::canCreate(CompanySubscription::RESOURCE_PENDAPATAN_LAINS)
                && ! ProFeatures::actorIsSuperAdmin())
            ->tooltip(fn () => CompanySubscription::canCreate(CompanySubscription::RESOURCE_PENDAPATAN_LAINS)
                || ProFeatures::actorIsSuperAdmin()
                ? null
                : CompanySubscription::fullMessage(CompanySubscription::RESOURCE_PENDAPATAN_LAINS));

        return $actions;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PendapatanLainOverviewWidget::class,
        ];
    }
}
