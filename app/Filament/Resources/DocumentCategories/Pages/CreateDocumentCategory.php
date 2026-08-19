<?php

namespace App\Filament\Resources\DocumentCategories\Pages;

use App\Filament\Resources\DocumentCategories\DocumentCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDocumentCategory extends CreateRecord
{
    protected static string $resource = DocumentCategoryResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = null;

        if (empty($data['format_number'])) {
            $data['format_number'] = '{SEQ}/{CAT}/{CO}/{ROMAN_MONTH}/{Y}';
        }

        return $data;
    }
}
