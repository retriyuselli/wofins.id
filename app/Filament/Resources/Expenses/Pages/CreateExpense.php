<?php

namespace App\Filament\Resources\Expenses\Pages;

use App\Filament\Concerns\EnforcesSubscriptionQuota;
use App\Filament\Resources\Expenses\ExpenseResource;
use App\Support\CompanySubscription;
use App\Support\UserVisibility;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateExpense extends CreateRecord
{
    use EnforcesSubscriptionQuota;

    protected static string $resource = ExpenseResource::class;

    protected function subscriptionResource(): string
    {
        return CompanySubscription::RESOURCE_EXPENSES;
    }

    public function mount(): void
    {
        parent::mount();

        Notification::make()
            ->title(CompanySubscription::planLabel())
            ->body(CompanySubscription::summary(CompanySubscription::RESOURCE_EXPENSES))
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
