<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Filament\Resources\Documents\DocumentResource;
use App\Support\ProFeatures;
use App\Support\UserVisibility;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateDocument extends CreateRecord
{
    protected static string $resource = DocumentResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id() ?? UserVisibility::teamRootId();

        if (empty($data['company_id'])) {
            $data = UserVisibility::stampCompanyId($data);
        }

        if (empty($data['company_id'])) {
            Notification::make()
                ->title('Company belum terhubung')
                ->body(ProFeatures::actorIsSuperAdmin()
                    ? 'Pilih perusahaan pada form dokumen.'
                    : 'Akun Anda belum punya Company.')
                ->danger()
                ->send();

            $this->halt();
        }

        return $data;
    }
}
