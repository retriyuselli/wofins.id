<?php

namespace App\Filament\Resources\SopCategories\Pages;

use App\Filament\Resources\SopCategories\SopCategoryResource;
use App\Support\UserVisibility;
use Filament\Resources\Pages\CreateRecord;

class CreateSopCategory extends CreateRecord
{
    protected static string $resource = SopCategoryResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return UserVisibility::stampCompanyId($data);
    }
}
