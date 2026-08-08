<?php

namespace App\Filament\Resources\PaymentMethods\Pages;

use App\Filament\Resources\PaymentMethods\PaymentMethodResource;
use App\Filament\Resources\PaymentMethods\Widgets\PaymentMethodStatsWidget;
use App\Support\CompanySubscription;
use App\Support\ProFeatures;
use App\Support\UserVisibility;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPaymentMethods extends ListRecords
{
    protected static string $resource = PaymentMethodResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [];

        if (UserVisibility::canViewTeamSeatSummary()) {
            $actions[] = Action::make('quota_rekening')
                ->label(CompanySubscription::summary(CompanySubscription::RESOURCE_PAYMENT_METHODS))
                ->icon('heroicon-o-credit-card')
                ->color(CompanySubscription::canCreate(CompanySubscription::RESOURCE_PAYMENT_METHODS) ? 'gray' : 'warning')
                ->disabled()
                ->extraAttributes(['class' => 'pointer-events-none']);
        }

        $actions[] = CreateAction::make()
            ->disabled(fn () => ! CompanySubscription::canCreate(CompanySubscription::RESOURCE_PAYMENT_METHODS)
                && ! ProFeatures::actorIsSuperAdmin())
            ->tooltip(fn () => CompanySubscription::canCreate(CompanySubscription::RESOURCE_PAYMENT_METHODS)
                || ProFeatures::actorIsSuperAdmin()
                ? null
                : CompanySubscription::fullMessage(CompanySubscription::RESOURCE_PAYMENT_METHODS));

        return $actions;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PaymentMethodStatsWidget::class,
        ];
    }
}
