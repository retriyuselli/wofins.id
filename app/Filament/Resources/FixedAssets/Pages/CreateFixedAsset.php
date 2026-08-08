<?php

namespace App\Filament\Resources\FixedAssets\Pages;

use App\Filament\Concerns\EnforcesSubscriptionQuota;
use App\Filament\Resources\FixedAssets\FixedAssetResource;
use App\Models\FixedAsset;
use App\Support\CompanySubscription;
use App\Support\ProFeatures;
use App\Support\UserVisibility;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateFixedAsset extends CreateRecord
{
    use EnforcesSubscriptionQuota;

    protected static string $resource = FixedAssetResource::class;

    protected function subscriptionResource(): string
    {
        return CompanySubscription::RESOURCE_FIXED_ASSETS;
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
            ->body(CompanySubscription::summary(CompanySubscription::RESOURCE_FIXED_ASSETS))
            ->info()
            ->send();
    }

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

        // Generate asset code if not provided
        if (empty($data['asset_code'])) {
            $data['asset_code'] = FixedAsset::generateAssetCode($data['category'] ?? '');
        }

        // Set initial book value to purchase price
        $data['current_book_value'] = $data['purchase_price'];

        // Ensure accumulated depreciation starts at 0
        $data['accumulated_depreciation'] = 0;

        return $data;
    }

    protected function afterCreate(): void
    {
        Notification::make()
            ->title('Fixed Asset Created')
            ->body("Asset {$this->record->asset_code} has been successfully created.")
            ->success()
            ->send();
    }
}
