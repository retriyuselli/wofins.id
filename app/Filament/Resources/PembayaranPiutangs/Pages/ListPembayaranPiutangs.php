<?php

namespace App\Filament\Resources\PembayaranPiutangs\Pages;

use App\Filament\Resources\PembayaranPiutangs\PembayaranPiutangResource;
use App\Support\CompanySubscription;
use App\Support\ProFeatures;
use App\Support\UserVisibility;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPembayaranPiutangs extends ListRecords
{
    protected static string $resource = PembayaranPiutangResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [];

        if (UserVisibility::canViewTeamSeatSummary()) {
            $actions[] = Action::make('quota_pembayaran_piutang')
                ->label(CompanySubscription::summary(CompanySubscription::RESOURCE_PEMBAYARAN_PIUTANGS))
                ->icon('heroicon-o-banknotes')
                ->color(CompanySubscription::canCreate(CompanySubscription::RESOURCE_PEMBAYARAN_PIUTANGS) ? 'gray' : 'warning')
                ->disabled()
                ->extraAttributes(['class' => 'pointer-events-none']);
        }

        $actions[] = CreateAction::make()
            ->disabled(fn () => ! CompanySubscription::canCreate(CompanySubscription::RESOURCE_PEMBAYARAN_PIUTANGS)
                && ! ProFeatures::actorIsSuperAdmin())
            ->tooltip(fn () => CompanySubscription::canCreate(CompanySubscription::RESOURCE_PEMBAYARAN_PIUTANGS)
                || ProFeatures::actorIsSuperAdmin()
                ? null
                : CompanySubscription::fullMessage(CompanySubscription::RESOURCE_PEMBAYARAN_PIUTANGS));

        return $actions;
    }
}
