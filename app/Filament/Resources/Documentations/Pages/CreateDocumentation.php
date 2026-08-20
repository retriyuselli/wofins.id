<?php

namespace App\Filament\Resources\Documentations\Pages;

use App\Filament\Resources\Documentations\DocumentationResource;
use App\Support\UserVisibility;
use Filament\Resources\Pages\CreateRecord;

class CreateDocumentation extends CreateRecord
{
    protected static string $resource = DocumentationResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return UserVisibility::stampCompanyId($data);
    }
}
