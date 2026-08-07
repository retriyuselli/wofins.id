<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Concerns\EnforcesSubscriptionQuota;
use App\Filament\Resources\Products\ProductResource;
use App\Support\CompanySubscription;
use App\Support\UserVisibility;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateProduct extends CreateRecord
{
    use EnforcesSubscriptionQuota;

    protected static string $resource = ProductResource::class;

    protected function subscriptionResource(): string
    {
        return CompanySubscription::RESOURCE_PRODUCTS;
    }

    public function mount(): void
    {
        parent::mount();

        Notification::make()
            ->title(CompanySubscription::planLabel())
            ->body(CompanySubscription::summary(CompanySubscription::RESOURCE_PRODUCTS))
            ->info()
            ->send();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['slug'] = Str::slug($data['name']);

        return UserVisibility::stampTeamOwner($data, 'created_by');
    }
}
