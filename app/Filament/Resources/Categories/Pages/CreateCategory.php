<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Concerns\EnforcesSubscriptionQuota;
use App\Filament\Resources\Categories\CategoryResource;
use App\Support\CompanySubscription;
use App\Support\ProFeatures;
use App\Support\UserVisibility;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateCategory extends CreateRecord
{
    use EnforcesSubscriptionQuota;

    protected static string $resource = CategoryResource::class;

    protected function subscriptionResource(): string
    {
        return CompanySubscription::RESOURCE_CATEGORIES;
    }

    public function mount(): void
    {
        parent::mount();

        Notification::make()
            ->title(CompanySubscription::planLabel())
            ->body(CompanySubscription::summary(CompanySubscription::RESOURCE_CATEGORIES))
            ->info()
            ->send();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['company_id'])) {
            $data = UserVisibility::stampCompanyId($data);
        }

        if (empty($data['company_id'])) {
            Notification::make()
                ->title('Company belum terhubung')
                ->body(ProFeatures::actorIsSuperAdmin()
                    ? 'Pilih perusahaan pada form kategori.'
                    : 'Akun Anda belum punya Company. Pastikan sudah di-Approve sebagai pemilik paket.')
                ->danger()
                ->send();

            $this->halt();
        }

        return $data;
    }
}
