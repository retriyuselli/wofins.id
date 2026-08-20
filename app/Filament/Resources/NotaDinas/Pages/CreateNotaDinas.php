<?php

namespace App\Filament\Resources\NotaDinas\Pages;

use App\Filament\Resources\NotaDinas\NotaDinasResource;
use App\Support\UserVisibility;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateNotaDinas extends CreateRecord
{
    protected static string $resource = NotaDinasResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['pengirim_id'] = $data['pengirim_id'] ?? Auth::id() ?? UserVisibility::teamRootId();

        return UserVisibility::stampCompanyId($data, 'pengirim_id');
    }
}
