<?php

namespace App\Filament\Resources\Piutangs\Pages;

use App\Filament\Concerns\EnforcesSubscriptionQuota;
use App\Filament\Resources\Piutangs\PiutangResource;
use App\Support\CompanySubscription;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreatePiutang extends CreateRecord
{
    use EnforcesSubscriptionQuota;

    protected static string $resource = PiutangResource::class;

    protected function subscriptionResource(): string
    {
        return CompanySubscription::RESOURCE_PIUTANGS;
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
            ->body(CompanySubscription::summary(CompanySubscription::RESOURCE_PIUTANGS))
            ->info()
            ->send();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['dibuat_oleh'] = Auth::id();

        // Set sisa_piutang sama dengan total_piutang saat buat baru
        $data['sisa_piutang'] = $data['total_piutang'];
        $data['sudah_dibayar'] = 0;

        return $data;
    }
}
