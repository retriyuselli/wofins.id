<?php

namespace App\Filament\Resources\PaymentMethods\Pages;

use App\Filament\Resources\PaymentMethods\PaymentMethodResource;
use App\Support\ProFeatures;
use App\Support\UserVisibility;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPaymentMethod extends EditRecord
{
    protected static string $resource = PaymentMethodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (ProFeatures::actorIsSuperAdmin()) {
            return $data;
        }

        // Tenant tidak boleh pindah/hapus company_id rekening.
        $data['company_id'] = UserVisibility::companyId()
            ?? $this->record->company_id;

        return $data;
    }
}
