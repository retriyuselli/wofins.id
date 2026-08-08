<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use App\Support\CompanySubscription;
use App\Support\ProFeatures;
use App\Support\UserVisibility;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCategories extends ListRecords
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [];

        if (UserVisibility::canViewTeamSeatSummary()) {
            $actions[] = Action::make('quota_kategori')
                ->label(CompanySubscription::summary(CompanySubscription::RESOURCE_CATEGORIES))
                ->icon('heroicon-o-tag')
                ->color(CompanySubscription::canCreate(CompanySubscription::RESOURCE_CATEGORIES) ? 'gray' : 'warning')
                ->disabled()
                ->extraAttributes(['class' => 'pointer-events-none']);
        }

        $actions[] = CreateAction::make()
            ->disabled(fn () => (! CompanySubscription::canCreate(CompanySubscription::RESOURCE_CATEGORIES)
                && ! ProFeatures::actorIsSuperAdmin())
                || ! CategoryResource::canCreate())
            ->tooltip(fn () => ! CategoryResource::canCreate()
                ? 'Hanya admin / pemilik paket yang dapat menambah kategori'
                : (CompanySubscription::canCreate(CompanySubscription::RESOURCE_CATEGORIES)
                    || ProFeatures::actorIsSuperAdmin()
                    ? null
                    : CompanySubscription::fullMessage(CompanySubscription::RESOURCE_CATEGORIES)));

        return $actions;
    }
}
