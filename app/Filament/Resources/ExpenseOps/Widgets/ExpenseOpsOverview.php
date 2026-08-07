<?php

namespace App\Filament\Resources\ExpenseOps\Widgets;

use App\Models\ExpenseOps;
use App\Support\UserVisibility;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class ExpenseOpsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $base = UserVisibility::constrainExpenseOpsQuery(ExpenseOps::query());

        $currentMonthOpsExpenses = (clone $base)->whereBetween('date_expense', [
            Carbon::now()->startOfMonth(),
            Carbon::now()->endOfMonth(),
        ])->sum('amount');

        $previousMonthOpsExpenses = (clone $base)->whereBetween('date_expense', [
            Carbon::now()->subMonth()->startOfMonth(),
            Carbon::now()->subMonth()->endOfMonth(),
        ])->sum('amount');

        $changePercentage = 0;
        if ($previousMonthOpsExpenses > 0) {
            $changePercentage = (($currentMonthOpsExpenses - $previousMonthOpsExpenses) / $previousMonthOpsExpenses) * 100;
        } elseif ($currentMonthOpsExpenses > 0) {
            $changePercentage = 100;
        }

        $trendIcon = null;
        $trendColor = 'gray';
        if ($changePercentage > 0) {
            $trendIcon = 'heroicon-m-arrow-trending-up';
            $trendColor = 'danger';
        } elseif ($changePercentage < 0) {
            $trendIcon = 'heroicon-m-arrow-trending-down';
            $trendColor = 'success';
        }

        $changeDescription = number_format(abs($changePercentage), 1).'% '.($changePercentage >= 0 ? 'naik' : 'turun');

        $opsExpensesWithoutImageCount = (clone $base)->where(function ($q) {
            $q->whereNull('image')->orWhere('image', '');
        })->count();

        $currentYearOpsExpenses = (clone $base)->whereYear('date_expense', Carbon::now()->year)->sum('amount');
        $currentYearOpsExpensesCount = (clone $base)->whereYear('date_expense', Carbon::now()->year)->count();

        return [
            Stat::make('Total Pengeluaran Operasional (Bulan Ini)', ''.number_format($currentMonthOpsExpenses, 0, ',', '.'))
                ->description($changeDescription.' dari bulan lalu')
                ->descriptionIcon($trendIcon, IconPosition::Before)
                ->color($trendColor)
                ->url(route('expense-ops.pdf-report', [
                    'year' => Carbon::now()->year,
                    'month' => Carbon::now()->month,
                ])),

            Stat::make('Total Pengeluaran Operasional (Tahun Ini)', ''.number_format($currentYearOpsExpenses, 0, ',', '.'))
                ->description('Total pengeluaran tahun ini')
                ->descriptionIcon('heroicon-m-banknotes', IconPosition::Before)
                ->color('primary'),

            Stat::make('Total Transaksi (Tahun Ini)', $currentYearOpsExpensesCount)
                ->description('Jumlah catatan pengeluaran tahun ini')
                ->descriptionIcon('heroicon-m-receipt-percent', IconPosition::Before)
                ->color('success'),

            Stat::make('Pengeluaran Tanpa Bukti', $opsExpensesWithoutImageCount)
                ->description('Catatan yang memerlukan bukti pembayaran')
                ->descriptionIcon('heroicon-m-exclamation-triangle', IconPosition::Before)
                ->color('warning'),
        ];
    }
}
