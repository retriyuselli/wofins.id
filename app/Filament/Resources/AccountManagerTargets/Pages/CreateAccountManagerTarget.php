<?php

namespace App\Filament\Resources\AccountManagerTargets\Pages;

use App\Filament\Resources\AccountManagerTargets\AccountManagerTargetResource;
use App\Support\UserVisibility;
use Filament\Resources\Pages\CreateRecord;

class CreateAccountManagerTarget extends CreateRecord
{
    protected static string $resource = AccountManagerTargetResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return UserVisibility::stampCompanyId($data, 'user_id');
    }
}
