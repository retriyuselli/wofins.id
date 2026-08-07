<?php

namespace App\Filament\Resources\SimulasiProduks\Pages;

use App\Filament\Concerns\EnforcesSubscriptionQuota;
use App\Filament\Resources\SimulasiProduks\SimulasiProdukResource;
use App\Models\Prospect;
use App\Models\SimulasiProduk;
use App\Support\CompanySubscription;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateSimulasiProduk extends CreateRecord
{
    use EnforcesSubscriptionQuota;

    protected static string $resource = SimulasiProdukResource::class;

    protected function subscriptionResource(): string
    {
        return CompanySubscription::RESOURCE_SIMULASI;
    }

    public function mount(): void
    {
        parent::mount();

        Notification::make()
            ->title(CompanySubscription::planLabel())
            ->body(CompanySubscription::summary(CompanySubscription::RESOURCE_SIMULASI))
            ->info()
            ->send();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        unset($data['name']);

        $base = (string) ($data['slug'] ?? '');
        if ($base === '' && isset($data['prospect_id'])) {
            $base = (string) Prospect::query()->whereKey($data['prospect_id'])->value('name_event');
        }

        if ($base !== '') {
            $data['slug'] = SimulasiProduk::generateUniqueSlug($base);
        }

        return $data;
    }
}
