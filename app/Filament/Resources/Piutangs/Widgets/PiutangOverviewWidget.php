<?php

namespace App\Filament\Resources\Piutangs\Widgets;

use App\Enums\StatusPiutang;
use App\Models\PembayaranPiutang;
use App\Models\Piutang;
use App\Support\UserVisibility;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class PiutangOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $base = UserVisibility::constrainOwnedQuery(Piutang::query(), 'dibuat_oleh');

        $totalPiutang = (clone $base)->sum('total_piutang');
        $totalSudahDibayar = (clone $base)->sum('sudah_dibayar');
        $totalSisaPiutang = (clone $base)->sum('sisa_piutang');

        $piutangAktif = (clone $base)->where('status', StatusPiutang::AKTIF)->count();
        $piutangLunas = (clone $base)->where('status', StatusPiutang::LUNAS)->count();
        $piutangJatuhTempo = (clone $base)->where('status', StatusPiutang::JATUH_TEMPO)->count();

        $piutangBulanIni = (clone $base)->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total_piutang');

        $piutangIds = (clone $base)->pluck('id');
        $pembayaranBulanIni = PembayaranPiutang::query()
            ->whereIn('piutang_id', $piutangIds)
            ->whereMonth('tanggal_pembayaran', now()->month)
            ->whereYear('tanggal_pembayaran', now()->year)
            ->where('status', 'dikonfirmasi')
            ->sum('total_pembayaran');

        return [
            Stat::make('Total Piutang', ''.Number::format($totalPiutang, 0))
                ->description('Seluruh piutang yang tercatat')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('info')
                ->chart([
                    (clone $base)->whereDate('created_at', '>=', now()->subDays(7))->sum('total_piutang'),
                    (clone $base)->whereDate('created_at', '>=', now()->subDays(6))->sum('total_piutang'),
                    (clone $base)->whereDate('created_at', '>=', now()->subDays(5))->sum('total_piutang'),
                    (clone $base)->whereDate('created_at', '>=', now()->subDays(4))->sum('total_piutang'),
                    (clone $base)->whereDate('created_at', '>=', now()->subDays(3))->sum('total_piutang'),
                    (clone $base)->whereDate('created_at', '>=', now()->subDays(2))->sum('total_piutang'),
                    (clone $base)->whereDate('created_at', '>=', now()->subDays(1))->sum('total_piutang'),
                ]),

            Stat::make('Sisa Piutang', ''.Number::format($totalSisaPiutang, 0))
                ->description('Yang belum dibayar')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('warning'),

            Stat::make('Sudah Dibayar', ''.Number::format($totalSudahDibayar, 0))
                ->description('Total pembayaran diterima')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Piutang Aktif', $piutangAktif)
                ->description('Masih dalam periode')
                ->descriptionIcon('heroicon-m-clock')
                ->color('primary'),

            Stat::make('Jatuh Tempo', $piutangJatuhTempo)
                ->description('Perlu tindak lanjut')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color('danger'),

            Stat::make('Piutang Bulan Ini', ''.Number::format($piutangBulanIni, 0))
                ->description('Piutang baru di '.now()->format('M Y'))
                ->descriptionIcon('heroicon-m-plus-circle')
                ->color('info'),

            Stat::make('Pembayaran Bulan Ini', ''.Number::format($pembayaranBulanIni, 0))
                ->description('Pembayaran di '.now()->format('M Y'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Tingkat Pelunasan',
                $totalPiutang > 0 ? number_format(($totalSudahDibayar / $totalPiutang) * 100, 1).'%' : '0%'
            )
                ->description('Persentase pembayaran')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($totalPiutang > 0 && ($totalSudahDibayar / $totalPiutang) > 0.8 ? 'success' : 'warning'),
        ];
    }
}
