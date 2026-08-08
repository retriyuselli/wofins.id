<?php

namespace App\Filament\Resources\PendapatanLains\Pages;

use App\Filament\Concerns\EnforcesSubscriptionQuota;
use App\Filament\Resources\PendapatanLains\PendapatanLainResource;
use App\Support\CompanySubscription;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreatePendapatanLain extends CreateRecord
{
    use EnforcesSubscriptionQuota;

    protected static string $resource = PendapatanLainResource::class;

    protected function subscriptionResource(): string
    {
        return CompanySubscription::RESOURCE_PENDAPATAN_LAINS;
    }

    public function mount(): void
    {
        parent::mount();

        Notification::make()
            ->title(CompanySubscription::planLabel())
            ->body(CompanySubscription::summary(CompanySubscription::RESOURCE_PENDAPATAN_LAINS))
            ->info()
            ->send();
    }
}
