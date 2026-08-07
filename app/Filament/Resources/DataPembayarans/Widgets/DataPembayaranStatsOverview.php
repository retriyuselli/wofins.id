<?php

namespace App\Filament\Resources\DataPembayarans\Widgets;

use App\Models\DataPembayaran;
use App\Support\UserVisibility;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class DataPembayaranStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $today = Carbon::today();
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $base = UserVisibility::constrainViaTeamOrders(DataPembayaran::query());

        $totalPembayaranHariIni = (clone $base)->whereDate('tgl_bayar', $today)->sum('nominal');
        $totalPembayaranMingguIni = (clone $base)->whereBetween('tgl_bayar', [$startOfWeek, $endOfWeek])->sum('nominal');
        $totalPembayaranBulanIni = (clone $base)->whereBetween('tgl_bayar', [$startOfMonth, $endOfMonth])->sum('nominal');
        $jumlahTransaksiBulanIni = (clone $base)->whereBetween('tgl_bayar', [$startOfMonth, $endOfMonth])->count();
        $jumlahTanpaImageTahunBerjalan = (clone $base)->whereYear('tgl_bayar', Carbon::now()->year)
            ->where(function ($q) {
                $q->whereNull('image')->orWhere('image', '');
            })
            ->count();

        return [
            Stat::make('Pembayaran Hari Ini', ''.number_format($totalPembayaranHariIni, 0, ',', '.'))
                ->description('Total pembayaran yang diterima hari ini')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),
            Stat::make('Pembayaran Minggu Ini', ''.number_format($totalPembayaranMingguIni, 0, ',', '.'))
                ->description('Total pembayaran yang diterima minggu ini')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),
            Stat::make('Pembayaran Bulan Ini', ''.number_format($totalPembayaranBulanIni, 0, ',', '.'))
                ->description($jumlahTransaksiBulanIni.' transaksi bulan ini')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('primary'),
            Stat::make('Tanpa Payment Proof Tahun Berjalan', ''.number_format($jumlahTanpaImageTahunBerjalan, 0, ',', '.'))
                ->description('Transaksi tanpa payment proof tahun berjalan')
                ->descriptionIcon('heroicon-m-photo')
                ->color('warning'),
        ];
    }
}
