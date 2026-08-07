<?php

namespace App\Filament\Resources\DataPembayarans\Widgets;

use App\Models\Order;
use App\Support\UserVisibility;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class InvoiceStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $orders = UserVisibility::constrainOrdersQuery(Order::query());

        $belumLunasCount = (clone $orders)->where('is_paid', false)->count();
        $lunasCount = (clone $orders)->where('is_paid', true)->count();

        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        $acaraBulanIniBelumLunas = (clone $orders)->where('is_paid', false)
            ->whereHas('prospect', function ($query) use ($currentMonth, $currentYear) {
                $query->whereMonth('date_resepsi', $currentMonth)
                    ->whereYear('date_resepsi', $currentYear);
            })
            ->count();

        $acaraBulanIniLunas = (clone $orders)->where('is_paid', true)
            ->whereHas('prospect', function ($query) use ($currentMonth, $currentYear) {
                $query->whereMonth('date_resepsi', $currentMonth)
                    ->whereYear('date_resepsi', $currentYear);
            })
            ->count();

        return [
            Stat::make('Belum Lunas', $belumLunasCount)
                ->description('Total invoice belum lunas')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),

            Stat::make('Lunas', $lunasCount)
                ->description('Total invoice lunas')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Acara Bulan Ini (Belum Lunas)', $acaraBulanIniBelumLunas)
                ->description('Acara bulan ini yang belum lunas')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('warning'),

            Stat::make('Acara Bulan Ini (Lunas)', $acaraBulanIniLunas)
                ->description('Acara bulan ini yang sudah lunas')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('success'),
        ];
    }
}
