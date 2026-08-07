<?php

namespace App\Filament\Resources\Expenses\Widgets;

use App\Models\Expense;
use App\Support\UserVisibility;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class ExpenseOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $base = UserVisibility::constrainViaTeamOrders(Expense::query());

        $currentMonthExpenses = (clone $base)->whereBetween('date_expense', [
            Carbon::now()->startOfMonth(),
            Carbon::now()->endOfMonth(),
        ])->sum('amount');

        $previousMonthExpenses = (clone $base)->whereBetween('date_expense', [
            Carbon::now()->subMonth()->startOfMonth(),
            Carbon::now()->subMonth()->endOfMonth(),
        ])->sum('amount');

        $changePercentage = 0;
        if ($previousMonthExpenses > 0) {
            $changePercentage = (($currentMonthExpenses - $previousMonthExpenses) / $previousMonthExpenses) * 100;
        } elseif ($currentMonthExpenses > 0) {
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

        $expensesWithoutImageCount = (clone $base)->where(function ($q) {
            $q->whereNull('image')->orWhere('image', '');
        })->count();

        $currentYearExpensesCount = (clone $base)->whereYear('date_expense', Carbon::now()->year)->count();

        return [
            Stat::make('Total Pengeluaran (Bulan Ini)', ''.number_format($currentMonthExpenses, 0, ',', '.'))
                ->description($changeDescription.' dari bulan lalu')
                ->descriptionIcon($trendIcon, IconPosition::Before)
                ->color($trendColor),

            Stat::make('Jumlah Pengeluaran (Tahun Ini)', $currentYearExpensesCount)
                ->description('Total catatan pengeluaran tahun ini')
                ->descriptionIcon('heroicon-m-document-duplicate', IconPosition::Before)
                ->color('info'),

            Stat::make('Pengeluaran Tanpa Bukti', $expensesWithoutImageCount)
                ->description('Catatan yang memerlukan bukti pembayaran')
                ->descriptionIcon('heroicon-m-exclamation-triangle', IconPosition::Before)
                ->color('warning'),
        ];
    }
}
