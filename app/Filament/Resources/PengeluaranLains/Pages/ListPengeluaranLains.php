<?php

namespace App\Filament\Resources\PengeluaranLains\Pages;

use App\Filament\Actions\GeneratePengeluaranLainAction;
use App\Filament\Resources\PengeluaranLains\PengeluaranLainResource;
use App\Filament\Resources\PengeluaranLains\Widgets\PengeluaranOverviewWidgets;
use App\Support\CompanySubscription;
use App\Support\ProFeatures;
use App\Support\UserVisibility;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPengeluaranLains extends ListRecords
{
    protected static string $resource = PengeluaranLainResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [
            GeneratePengeluaranLainAction::make(),
        ];

        if (UserVisibility::canViewTeamSeatSummary()) {
            $actions[] = Action::make('quota_pengeluaran_lain')
                ->label(CompanySubscription::summary(CompanySubscription::RESOURCE_PENGELUARAN_LAINS))
                ->icon('heroicon-o-arrow-trending-down')
                ->color(CompanySubscription::canCreate(CompanySubscription::RESOURCE_PENGELUARAN_LAINS) ? 'gray' : 'warning')
                ->disabled()
                ->extraAttributes(['class' => 'pointer-events-none']);
        }

        $actions[] = CreateAction::make()
            ->label('Tambah Pengeluaran Lainnya')
            ->icon('heroicon-o-plus')
            ->disabled(fn () => ! CompanySubscription::canCreate(CompanySubscription::RESOURCE_PENGELUARAN_LAINS)
                && ! ProFeatures::actorIsSuperAdmin())
            ->tooltip(fn () => CompanySubscription::canCreate(CompanySubscription::RESOURCE_PENGELUARAN_LAINS)
                || ProFeatures::actorIsSuperAdmin()
                ? null
                : CompanySubscription::fullMessage(CompanySubscription::RESOURCE_PENGELUARAN_LAINS));

        return $actions;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PengeluaranOverviewWidgets::class,
        ];
    }
}
