<?php

namespace App\Filament\Resources\PembayaranPiutangs\Pages;

use App\Filament\Concerns\EnforcesSubscriptionQuota;
use App\Filament\Resources\PembayaranPiutangs\PembayaranPiutangResource;
use App\Support\CompanySubscription;
use App\Support\UserVisibility;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreatePembayaranPiutang extends CreateRecord
{
    use EnforcesSubscriptionQuota;

    protected static string $resource = PembayaranPiutangResource::class;

    protected function subscriptionResource(): string
    {
        return CompanySubscription::RESOURCE_PEMBAYARAN_PIUTANGS;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function mount(): void
    {
        parent::mount();

        Notification::make()
            ->title(CompanySubscription::planLabel())
            ->body(CompanySubscription::summary(CompanySubscription::RESOURCE_PEMBAYARAN_PIUTANGS))
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

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Pembayaran Piutang berhasil dicatat')
            ->body('Pembayaran piutang telah berhasil disimpan.');
    }
}
