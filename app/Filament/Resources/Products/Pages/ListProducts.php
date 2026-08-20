<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\SimulasiProduks\SimulasiProdukResource;
use App\Support\CompanySubscription;
use App\Support\UserVisibility;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        $canCreate = CompanySubscription::canCreate(CompanySubscription::RESOURCE_PRODUCTS);
        $actions = [];

        if (UserVisibility::canViewGlobalUserAggregates()) {
            $actions[] = Action::make('subscription_quota')
                ->label(CompanySubscription::summary(CompanySubscription::RESOURCE_PRODUCTS))
                ->icon('heroicon-o-ticket')
                ->color($canCreate ? 'gray' : 'warning')
                ->disabled()
                ->extraAttributes(['class' => 'pointer-events-none']);
        }

        $actions[] = CreateAction::make()
            ->label('Create Product')
            ->disabled(fn () => ! $canCreate)
            ->tooltip(fn () => $canCreate
                ? null
                : CompanySubscription::fullMessage(CompanySubscription::RESOURCE_PRODUCTS))
            ->requiresConfirmation()
            ->modalHeading('Konfirmasi Pembuatan Produk Baru')
            ->modalDescription('Pastikan kembali data yang akan Anda isi sudah benar sebelum melanjutkan.')
            ->modalSubmitActionLabel('Lanjutkan')
            ->modalCancelActionLabel('Batal');

        $actions[] = Action::make('penawaran')
            ->label('Penawaran')
            ->color('success')
            ->icon('heroicon-o-eye')
            ->url(SimulasiProdukResource::getUrl('create'))
            ->openUrlInNewTab();

        return $actions;
    }
}
