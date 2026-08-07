<?php

namespace App\Filament\Concerns;

use App\Support\CompanySubscription;
use Filament\Notifications\Notification;

trait EnforcesSubscriptionQuota
{
    /**
     * Resource key: users | vendors | products
     */
    abstract protected function subscriptionResource(): string;

    protected function beforeCreate(): void
    {
        $resource = $this->subscriptionResource();

        if (CompanySubscription::canCreate($resource)) {
            return;
        }

        Notification::make()
            ->title('Kuota paket penuh')
            ->body(CompanySubscription::fullMessage($resource))
            ->warning()
            ->persistent()
            ->send();

        $this->halt();
    }
}
