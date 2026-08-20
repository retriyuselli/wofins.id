<?php

namespace App\Filament\Resources\Sops\Pages;

use App\Filament\Resources\Sops\SopResource;
use App\Support\UserVisibility;
use Filament\Resources\Pages\CreateRecord;

class CreateSop extends CreateRecord
{
    protected static string $resource = SopResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = UserVisibility::stampTeamOwner($data, 'created_by');

        return UserVisibility::stampCompanyId($data);
    }
}
