<?php

namespace App\Filament\Resources\Vendors\Widgets;

use App\Models\Vendor;
use App\Support\UserVisibility;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class VendorOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function scopedVendors(): Builder
    {
        return UserVisibility::constrainOwnedQuery(Vendor::query(), 'created_by');
    }

    protected function getStats(): array
    {
        try {
            $base = $this->scopedVendors();
            $totalVendors = (clone $base)->count();
            $regularVendors = (clone $base)->where('is_master', false)->count();

            $inUseVendors = (clone $base)
                ->where(function ($query) {
                    $query->whereHas('productVendors')
                        ->orWhereHas('expenses')
                        ->orWhereHas('notaDinasDetails')
                        ->orWhereHas('productPenambahans');
                })
                ->count();

            $availableVendors = (clone $base)
                ->whereDoesntHave('productVendors')
                ->whereDoesntHave('expenses')
                ->whereDoesntHave('notaDinasDetails')
                ->whereDoesntHave('productPenambahans')
                ->count();

            return [
                Stat::make('Total Vendors', $totalVendors)
                    ->icon('heroicon-o-users')
                    ->color('primary'),

                Stat::make('Profit Rp 0', (clone $base)->where('profit_amount', 0)->where('status', 'product')->count())
                    ->icon('heroicon-o-banknotes')
                    ->color('danger')
                    ->description('Vendor (Product) dengan keuntungan Rp 0'),

                Stat::make('Regular', $regularVendors)
                    ->icon('heroicon-o-minus-circle')
                    ->color('gray')
                    ->description('Vendor biasa'),

                Stat::make('In Use', $inUseVendors)
                    ->icon('heroicon-o-link')
                    ->color('warning')
                    ->description('Dipakai di produk/biaya/dll'),

                Stat::make('Available', $availableVendors)
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->description('Belum dipakai di mana pun'),

                Stat::make('Status Master', (clone $base)->where('is_master', true)->count())
                    ->icon('heroicon-o-check-badge')
                    ->color('info')
                    ->description('Vendor dengan status Master'),
            ];
        } catch (\Throwable $e) {
            return [
                Stat::make('Total Vendors', 0)
                    ->icon('heroicon-o-users')
                    ->color('primary'),

                Stat::make('Profit Rp 0', 0)
                    ->icon('heroicon-o-banknotes')
                    ->color('danger')
                    ->description('Vendor dengan keuntungan Rp 0'),

                Stat::make('Regular', 0)
                    ->icon('heroicon-o-minus-circle')
                    ->color('gray')
                    ->description('Vendor biasa'),

                Stat::make('In Use', 0)
                    ->icon('heroicon-o-link')
                    ->color('warning')
                    ->description('Dipakai di produk/biaya/dll'),

                Stat::make('Available', 0)
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->description('Belum dipakai di mana pun'),
            ];
        }
    }
}
