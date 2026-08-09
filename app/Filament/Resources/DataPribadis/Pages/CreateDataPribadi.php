<?php

namespace App\Filament\Resources\DataPribadis\Pages;

use App\Filament\Resources\DataPribadis\DataPribadiResource;
use App\Support\ProFeatures;
use App\Support\UserVisibility;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateDataPribadi extends CreateRecord
{
    protected static string $resource = DataPribadiResource::class;

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
