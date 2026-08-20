<?php

namespace App\Filament\Resources\DataPembayarans\Pages;

use App\Filament\Concerns\EnforcesSubscriptionQuota;
use App\Filament\Resources\DataPembayarans\DataPembayaranResource;
use App\Support\CompanySubscription;
use App\Support\UserVisibility;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateDataPembayaran extends CreateRecord
{
    use EnforcesSubscriptionQuota;

    protected static string $resource = DataPembayaranResource::class;

    protected function subscriptionResource(): string
    {
        return CompanySubscription::RESOURCE_DATA_PEMBAYARANS;
    }

    public function mount(): void
    {
        parent::mount();

        Notification::make()
            ->title(CompanySubscription::planLabel())
            ->body(CompanySubscription::summary(CompanySubscription::RESOURCE_DATA_PEMBAYARANS))
            ->info()
            ->send();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return UserVisibility::stampCompanyIdFromPaymentMethod($data);
    }
}
