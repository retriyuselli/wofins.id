<?php

namespace App\Filament\Resources\ProspectApps\Pages;

use App\Filament\Resources\ProspectApps\ProspectAppResource;
use App\Support\UserVisibility;
use Filament\Resources\Pages\CreateRecord;

class CreateProspectApp extends CreateRecord
{
    protected static string $resource = ProspectAppResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return UserVisibility::stampCompanyId($data, 'user_id');
    }
}
