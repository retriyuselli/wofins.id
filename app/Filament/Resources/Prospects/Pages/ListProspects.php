<?php

namespace App\Filament\Resources\Prospects\Pages;

use App\Filament\Resources\Prospects\ProspectResource;
use App\Filament\Resources\Prospects\Widgets\ProspectOverviewWidget;
use App\Support\CompanySubscription;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProspects extends ListRecords
{
    protected static string $resource = ProspectResource::class;

    protected function getHeaderActions(): array
    {
        $canCreate = CompanySubscription::canCreate(CompanySubscription::RESOURCE_PROSPECTS);

        return [
            Action::make('subscription_quota')
                ->label(CompanySubscription::summary(CompanySubscription::RESOURCE_PROSPECTS))
                ->icon('heroicon-o-ticket')
                ->color($canCreate ? 'gray' : 'warning')
                ->disabled()
                ->extraAttributes(['class' => 'pointer-events-none']),

            Action::make('view_prospects_without_orders')
                ->label('Prospect Tanpa Order')
                ->icon('heroicon-m-exclamation-triangle')
                ->color('warning')
                ->badge(function () {
                    try {
                        return ProspectResource::getEloquentQuery()->doesntHave('orders')->count();
                    } catch (Exception $e) {
                        return 0;
                    }
                })
                ->url(fn () => static::getUrl(['tableFilters' => ['order_status' => ['value' => 'no_order']]])),

            CreateAction::make()
                ->disabled(fn () => ! $canCreate)
                ->tooltip(fn () => $canCreate
                    ? null
                    : CompanySubscription::fullMessage(CompanySubscription::RESOURCE_PROSPECTS)),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ProspectOverviewWidget::class,
        ];
    }
}
