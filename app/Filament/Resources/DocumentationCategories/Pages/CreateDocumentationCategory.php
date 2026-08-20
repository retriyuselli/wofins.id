<?php

namespace App\Filament\Resources\DocumentationCategories\Pages;

use App\Filament\Resources\DocumentationCategories\DocumentationCategoryResource;
use App\Support\UserVisibility;
use Filament\Resources\Pages\CreateRecord;

class CreateDocumentationCategory extends CreateRecord
{
    protected static string $resource = DocumentationCategoryResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return UserVisibility::stampCompanyId($data);
    }
}
