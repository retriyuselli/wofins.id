<?php

namespace App\Filament\Resources\ExpenseOps\Pages;

use App\Filament\Concerns\EnforcesSubscriptionQuota;
use App\Filament\Resources\ExpenseOps\ExpenseOpsResource;
use App\Support\CompanySubscription;
use App\Support\UserVisibility;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateExpenseOps extends CreateRecord
{
    use EnforcesSubscriptionQuota;

    protected static string $resource = ExpenseOpsResource::class;

    protected function subscriptionResource(): string
    {
        return CompanySubscription::RESOURCE_EXPENSE_OPS;
    }

    public function mount(): void
    {
        parent::mount();

        Notification::make()
            ->title(CompanySubscription::planLabel())
            ->body(CompanySubscription::summary(CompanySubscription::RESOURCE_EXPENSE_OPS))
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
