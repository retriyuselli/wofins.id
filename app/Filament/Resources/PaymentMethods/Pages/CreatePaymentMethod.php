<?php

namespace App\Filament\Resources\PaymentMethods\Pages;

use App\Filament\Concerns\EnforcesSubscriptionQuota;
use App\Filament\Resources\PaymentMethods\PaymentMethodResource;
use App\Support\CompanySubscription;
use App\Support\ProFeatures;
use App\Support\UserVisibility;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreatePaymentMethod extends CreateRecord
{
    use EnforcesSubscriptionQuota;

    protected static string $resource = PaymentMethodResource::class;

    protected function subscriptionResource(): string
    {
        return CompanySubscription::RESOURCE_PAYMENT_METHODS;
    }

    public function mount(): void
    {
        parent::mount();

        Notification::make()
            ->title(CompanySubscription::planLabel())
            ->body(CompanySubscription::summary(CompanySubscription::RESOURCE_PAYMENT_METHODS))
            ->info()
            ->send();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = UserVisibility::stampCompanyId($data);

        if (! ProFeatures::actorIsSuperAdmin() && empty($data['company_id'])) {
            Notification::make()
                ->title('Company belum terhubung')
                ->body('Akun Anda belum punya Company. Pastikan sudah di-Approve sebagai pemilik paket.')
                ->danger()
                ->send();

            $this->halt();
        }

        return $data;
    }
}
