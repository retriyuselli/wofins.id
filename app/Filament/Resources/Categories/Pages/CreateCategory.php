<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use App\Support\ProFeatures;
use App\Support\UserVisibility;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateCategory extends CreateRecord
{
    protected static string $resource = CategoryResource::class;

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
                    : 'Akun Anda belum punya Company.')
                ->danger()
                ->send();

            $this->halt();
        }

        return $data;
    }
}
