<?php

namespace App\Filament\Resources\PengeluaranLains\Pages;

use App\Filament\Concerns\EnforcesSubscriptionQuota;
use App\Filament\Resources\PengeluaranLains\PengeluaranLainResource;
use App\Support\CompanySubscription;
use App\Support\UserVisibility;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreatePengeluaranLain extends CreateRecord
{
    use EnforcesSubscriptionQuota;

    protected static string $resource = PengeluaranLainResource::class;

    protected function subscriptionResource(): string
    {
        return CompanySubscription::RESOURCE_PENGELUARAN_LAINS;
    }

    public function mount(): void
    {
        parent::mount();

        Notification::make()
            ->title(CompanySubscription::planLabel())
            ->body(CompanySubscription::summary(CompanySubscription::RESOURCE_PENGELUARAN_LAINS))
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
