<?php

namespace App\Filament\Resources\FixedAssets\Pages;

use App\Filament\Resources\FixedAssets\FixedAssetResource;
use App\Models\FixedAsset;
use App\Support\CompanySubscription;
use App\Support\ProFeatures;
use App\Support\UserVisibility;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListFixedAssets extends ListRecords
{
    protected static string $resource = FixedAssetResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [];

        if (UserVisibility::canViewTeamSeatSummary()) {
            $actions[] = Action::make('quota_aset')
                ->label(CompanySubscription::summary(CompanySubscription::RESOURCE_FIXED_ASSETS))
                ->icon('heroicon-o-building-office-2')
                ->color(CompanySubscription::canCreate(CompanySubscription::RESOURCE_FIXED_ASSETS) ? 'gray' : 'warning')
                ->disabled()
                ->extraAttributes(['class' => 'pointer-events-none']);
        }

        $actions[] = Action::make('bulk_depreciation')
            ->label('Hitung Semua Penyusutan')
            ->icon('heroicon-o-calculator')
            ->color('info')
            ->action(function () {
                $assets = FixedAsset::where('is_active', true)
                    ->whereColumn('current_book_value', '>', 'salvage_value')
                    ->get();

                $totalDepreciation = 0;
                $processedCount = 0;

                foreach ($assets as $asset) {
                    if (! $asset->isFullyDepreciated()) {
                        $monthlyDepreciation = $asset->calculateMonthlyDepreciation();
                        $asset->accumulated_depreciation += $monthlyDepreciation;
                        $asset->updateBookValue();
                        $totalDepreciation += $monthlyDepreciation;
                        $processedCount++;
                    }
                }

                Notification::make()
                    ->title('Penyusutan Bulk Selesai')
                    ->body("Diproses {$processedCount} aset. Total penyusutan: IDR ".number_format($totalDepreciation))
                    ->success()
                    ->send();
            })
            ->requiresConfirmation()
            ->modalHeading('Hitung Penyusutan untuk Semua Aset Aktif')
            ->modalDescription('Ini akan menghitung penyusutan bulanan untuk semua aset aktif yang belum sepenuhnya tersusut.')
            ->modalSubmitActionLabel('Hitung');

        $actions[] = CreateAction::make()
            ->label('Tambah Aset')
            ->disabled(fn () => ! CompanySubscription::canCreate(CompanySubscription::RESOURCE_FIXED_ASSETS)
                && ! ProFeatures::actorIsSuperAdmin())
            ->tooltip(fn () => CompanySubscription::canCreate(CompanySubscription::RESOURCE_FIXED_ASSETS)
                || ProFeatures::actorIsSuperAdmin()
                ? null
                : CompanySubscription::fullMessage(CompanySubscription::RESOURCE_FIXED_ASSETS));

        return $actions;
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua Aset'),

            'active' => Tab::make('Aktif')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', true))
                ->badge(FixedAsset::query()->where('is_active', true)->count()),

            'inactive' => Tab::make('Tidak Aktif')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', false))
                ->badge(FixedAsset::query()->where('is_active', false)->count()),

            'needs_maintenance' => Tab::make('Perlu Maintenance')
                ->modifyQueryUsing(fn (Builder $query) => $query->needsMaintenance())
                ->badge(FixedAsset::query()->needsMaintenance()->count()),

            'fully_depreciated' => Tab::make('Tersusut Penuh')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereColumn('current_book_value', '<=', 'salvage_value'))
                ->badge(FixedAsset::query()->whereColumn('current_book_value', '<=', 'salvage_value')->count()),
        ];
    }
}
