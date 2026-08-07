<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Filament\Resources\Documents\DocumentResource;
use App\Support\UserVisibility;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateDocument extends CreateRecord
{
    protected static string $resource = DocumentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Audit: siapa yang membuat; visibility tetap per tim lewat teamUserIds
        $data['created_by'] = Auth::id() ?? UserVisibility::teamRootId();

        return $data;
    }
}
