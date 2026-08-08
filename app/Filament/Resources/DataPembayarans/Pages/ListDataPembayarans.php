<?php

namespace App\Filament\Resources\DataPembayarans\Pages;

use App\Filament\Resources\DataPembayarans\DataPembayaranResource;
use App\Filament\Resources\DataPembayarans\Widgets\DataPembayaranStatsOverview;
use App\Filament\Resources\DataPembayarans\Widgets\InvoiceStatsOverview;
use App\Support\CompanySubscription;
use App\Support\UserVisibility;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListDataPembayarans extends ListRecords
{
    protected static string $resource = DataPembayaranResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [];

        if (UserVisibility::canViewTeamSeatSummary()) {
            $actions[] = Action::make('quota_pendapatan_wedding')
                ->label(CompanySubscription::summary(CompanySubscription::RESOURCE_DATA_PEMBAYARANS))
                ->icon('heroicon-o-receipt-percent')
                ->color(CompanySubscription::canCreate(CompanySubscription::RESOURCE_DATA_PEMBAYARANS) ? 'gray' : 'warning')
                ->disabled()
                ->extraAttributes(['class' => 'pointer-events-none']);
        }

        $actions[] = Action::make('downloadPdf')
            ->label('Download PDF')
            ->icon('heroicon-o-arrow-down-tray')
            ->url(route('data-pembayaran.pdf-report'))
            ->color('success')
            ->openUrlInNewTab();

        $actions[] = Action::make('viewHtmlReport')
            ->label('Laporan Pembayaran')
            ->icon('heroicon-o-document-text')
            ->url(route('data-pembayaran.html-report'))
            ->openUrlInNewTab()
            ->color('info');

        return $actions;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            DataPembayaranStatsOverview::class,
            InvoiceStatsOverview::class,
        ];
    }
}
