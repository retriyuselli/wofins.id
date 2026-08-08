<?php

namespace App\Filament\Resources\Companies\Pages;

use App\Filament\Resources\Companies\CompanyResource;
use App\Support\ProFeatures;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListCompanies extends ListRecords
{
    protected static string $resource = CompanyResource::class;

    public function mount(): void
    {
        parent::mount();

        // Pemilik paket: langsung ke edit Company miliknya.
        if (! ProFeatures::actorIsSuperAdmin()) {
            $company = Auth::user()?->company;

            if ($company) {
                $this->redirect(CompanyResource::getUrl('edit', ['record' => $company]));
            }
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn () => ProFeatures::actorIsSuperAdmin()),
        ];
    }
}
