<?php

namespace App\Filament\Resources\SimulasiProduks\Pages;

use App\Filament\Resources\SimulasiProduks\SimulasiProdukResource;
use App\Support\CompanySubscription;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSimulasiProduks extends ListRecords
{
    protected static string $resource = SimulasiProdukResource::class;

    protected function getHeaderActions(): array
    {
        $canCreate = CompanySubscription::canCreate(CompanySubscription::RESOURCE_SIMULASI);

        return [
            Action::make('subscription_quota')
                ->label(CompanySubscription::summary(CompanySubscription::RESOURCE_SIMULASI))
                ->icon('heroicon-o-ticket')
                ->color($canCreate ? 'gray' : 'warning')
                ->disabled()
                ->extraAttributes(['class' => 'pointer-events-none']),

            CreateAction::make()
                ->disabled(fn () => ! $canCreate)
                ->tooltip(fn () => $canCreate
                    ? null
                    : CompanySubscription::fullMessage(CompanySubscription::RESOURCE_SIMULASI)),
        ];
    }
}
