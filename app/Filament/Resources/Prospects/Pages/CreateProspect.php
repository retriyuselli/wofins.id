<?php

namespace App\Filament\Resources\Prospects\Pages;

use App\Filament\Concerns\EnforcesSubscriptionQuota;
use App\Filament\Resources\Prospects\ProspectResource;
use App\Support\CompanySubscription;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateProspect extends CreateRecord
{
    use EnforcesSubscriptionQuota;

    protected static string $resource = ProspectResource::class;

    protected function subscriptionResource(): string
    {
        return CompanySubscription::RESOURCE_PROSPECTS;
    }

    public function mount(): void
    {
        parent::mount();

        Notification::make()
            ->title(CompanySubscription::planLabel())
            ->body(CompanySubscription::summary(CompanySubscription::RESOURCE_PROSPECTS))
            ->info()
            ->send();
    }
}
