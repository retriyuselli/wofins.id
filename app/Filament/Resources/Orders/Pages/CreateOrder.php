<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Concerns\EnforcesSubscriptionQuota;
use App\Filament\Resources\Orders\OrderResource;
use App\Support\CompanySubscription;
use App\Support\UserVisibility;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateOrder extends CreateRecord
{
    use EnforcesSubscriptionQuota;

    protected static string $resource = OrderResource::class;

    protected function subscriptionResource(): string
    {
        return CompanySubscription::RESOURCE_ORDERS;
    }

    public function mount(): void
    {
        parent::mount();

        Notification::make()
            ->title(CompanySubscription::planLabel())
            ->body(CompanySubscription::summary(CompanySubscription::RESOURCE_ORDERS))
            ->info()
            ->send();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return UserVisibility::stampCompanyId($data, 'user_id');
    }
}
