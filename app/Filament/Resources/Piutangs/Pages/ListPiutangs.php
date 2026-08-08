<?php

namespace App\Filament\Resources\Piutangs\Pages;

use App\Filament\Resources\Piutangs\PiutangResource;
use App\Filament\Resources\Piutangs\Widgets\PiutangJatuhTempoWidget;
use App\Filament\Resources\Piutangs\Widgets\PiutangOverviewWidget;
use App\Filament\Resources\Piutangs\Widgets\TopDebiturWidget;
use App\Support\CompanySubscription;
use App\Support\ProFeatures;
use App\Support\UserVisibility;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPiutangs extends ListRecords
{
    protected static string $resource = PiutangResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [];

        if (UserVisibility::canViewTeamSeatSummary()) {
            $actions[] = Action::make('quota_piutang')
                ->label(CompanySubscription::summary(CompanySubscription::RESOURCE_PIUTANGS))
                ->icon('heroicon-o-currency-dollar')
                ->color(CompanySubscription::canCreate(CompanySubscription::RESOURCE_PIUTANGS) ? 'gray' : 'warning')
                ->disabled()
                ->extraAttributes(['class' => 'pointer-events-none']);
        }

        $actions[] = CreateAction::make()
            ->disabled(fn () => ! CompanySubscription::canCreate(CompanySubscription::RESOURCE_PIUTANGS)
                && ! ProFeatures::actorIsSuperAdmin())
            ->tooltip(fn () => CompanySubscription::canCreate(CompanySubscription::RESOURCE_PIUTANGS)
                || ProFeatures::actorIsSuperAdmin()
                ? null
                : CompanySubscription::fullMessage(CompanySubscription::RESOURCE_PIUTANGS));

        return $actions;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PiutangOverviewWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            PiutangJatuhTempoWidget::class,
            TopDebiturWidget::class,
        ];
    }
}
