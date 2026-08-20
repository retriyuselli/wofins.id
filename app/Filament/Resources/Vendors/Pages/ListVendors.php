<?php

namespace App\Filament\Resources\Vendors\Pages;

use App\Filament\Resources\Vendors\VendorResource;
use App\Support\CompanySubscription;
use App\Support\UserVisibility;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVendors extends ListRecords
{
    protected static string $resource = VendorResource::class;

    protected function getHeaderActions(): array
    {
        $canCreate = CompanySubscription::canCreate(CompanySubscription::RESOURCE_VENDORS);
        $actions = [];

        if (UserVisibility::canViewGlobalUserAggregates()) {
            $actions[] = Action::make('subscription_quota')
                ->label(CompanySubscription::summary(CompanySubscription::RESOURCE_VENDORS))
                ->icon('heroicon-o-ticket')
                ->color($canCreate ? 'gray' : 'warning')
                ->disabled()
                ->extraAttributes(['class' => 'pointer-events-none']);
        }

        $actions[] = CreateAction::make()
            ->icon('heroicon-o-plus')
            ->label('New Vendor')
            ->disabled(fn () => ! $canCreate)
            ->tooltip(fn () => $canCreate
                ? null
                : CompanySubscription::fullMessage(CompanySubscription::RESOURCE_VENDORS));

        return $actions;
    }
}
