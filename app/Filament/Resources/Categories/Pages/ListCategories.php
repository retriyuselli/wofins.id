<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCategories extends ListRecords
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn (): bool => CategoryResource::canCreate())
                ->tooltip(fn (): ?string => CategoryResource::canCreate()
                    ? null
                    : 'Hanya super admin yang dapat menambah kategori'),
        ];
    }
}
