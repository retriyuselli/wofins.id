<?php

namespace App\Filament\Resources\Products\Widgets;

use App\Models\Product;
use App\Support\UserVisibility;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProductOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $base = UserVisibility::constrainOwnedQuery(Product::query(), 'created_by');

        $totalProducts = (clone $base)->count();
        $activeProducts = (clone $base)->where('is_active', true)->count();
        $approvedProducts = (clone $base)->where('is_approved', true)->count();
        $withVendors = (clone $base)->whereHas('items')->count();
        $withoutVendors = (clone $base)->whereDoesntHave('items')->count();
        $inOrders = (clone $base)->whereHas('orders')->count();

        return [
            Stat::make('Total Products', $totalProducts)
                ->icon('heroicon-o-shopping-bag')
                ->color('primary'),

            Stat::make('Active', $activeProducts)
                ->icon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Approved', $approvedProducts)
                ->icon('heroicon-o-shield-check')
                ->color('success'),

            Stat::make('With Vendors', $withVendors)
                ->icon('heroicon-o-link')
                ->color('warning'),

            Stat::make('Without Vendors', $withoutVendors)
                ->icon('heroicon-o-minus-circle')
                ->color('gray'),

            Stat::make('In Orders', $inOrders)
                ->icon('heroicon-o-clipboard-document-list')
                ->color('primary'),
        ];
    }
}
