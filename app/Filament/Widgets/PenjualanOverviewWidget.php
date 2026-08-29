<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Support\PricingPlans;
use App\Support\ProFeatures;
use App\Support\UserVisibility;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class PenjualanOverviewWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 8;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Auth::check() && ProFeatures::allows(PricingPlans::FEATURE_PROJECTS);
    }

    public function getHeading(): ?string
    {
        return 'Penjualan';
    }

    public function getDescription(): ?string
    {
        $start = $this->pageFilters['startDate'] ?? now()->startOfMonth()->toDateString();
        $end = $this->pageFilters['endDate'] ?? now()->endOfMonth()->toDateString();

        $from = Carbon::parse($start)->translatedFormat('d M Y');
        $to = Carbon::parse($end)->translatedFormat('d M Y');

        return "Ringkasan closing dan omset ({$from} – {$to})";
    }

    protected function getStats(): array
    {
        $start = $this->pageFilters['startDate'] ?? now()->startOfMonth()->toDateString();
        $end = $this->pageFilters['endDate'] ?? now()->endOfMonth()->toDateString();

        $previousStart = Carbon::parse($start)->subMonthNoOverflow();
        $previousEnd = Carbon::parse($end)->subMonthNoOverflow();

        $cacheKey = 'dashboard:penjualan:'.UserVisibility::cacheScopeKey().':'
            .md5(implode('|', [
                (string) $start,
                (string) $end,
                $previousStart->toDateString(),
                $previousEnd->toDateString(),
            ]));

        [
            'orders' => $orders,
            'omset' => $omset,
            'terbayar' => $terbayar,
            'outstanding' => $outstanding,
            'prevOrders' => $prevOrders,
            'prevOmset' => $prevOmset,
            'prevTerbayar' => $prevTerbayar,
            'sparkline' => $sparkline,
        ] = Cache::remember($cacheKey, 60, function () use ($start, $end, $previousStart, $previousEnd): array {
            $orders = $this->countClosings($start, $end);
            $omset = $this->sumOmset($start, $end);
            $terbayar = $this->sumTerbayar($start, $end);
            $prevOrders = $this->countClosings($previousStart->toDateString(), $previousEnd->toDateString());
            $prevOmset = $this->sumOmset($previousStart->toDateString(), $previousEnd->toDateString());
            $prevTerbayar = $this->sumTerbayar($previousStart->toDateString(), $previousEnd->toDateString());

            return [
                'orders' => $orders,
                'omset' => $omset,
                'terbayar' => $terbayar,
                'outstanding' => max(0, $omset - $terbayar),
                'prevOrders' => $prevOrders,
                'prevOmset' => $prevOmset,
                'prevTerbayar' => $prevTerbayar,
                'sparkline' => $this->omsetSparkline(),
            ];
        });

        $ordersUrl = OrderResource::getUrl('index');

        return [
            Stat::make('Closing baru', number_format($orders, 0, ',', '.'))
                ->icon('heroicon-o-shopping-bag')
                ->description($this->changeDescription($orders, $prevOrders, 'Closing'))
                ->descriptionIcon($orders >= $prevOrders ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($orders >= $prevOrders ? 'success' : 'danger')
                ->url($ordersUrl),

            Stat::make('Omset penjualan', 'Rp '.number_format($omset, 0, ',', '.'))
                ->icon('heroicon-o-banknotes')
                ->description($this->changeDescription($omset, $prevOmset, 'Omset'))
                ->descriptionIcon($omset >= $prevOmset ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($omset >= $prevOmset ? 'success' : 'danger')
                ->chart($sparkline)
                ->url($ordersUrl),

            Stat::make('Terbayar', 'Rp '.number_format($terbayar, 0, ',', '.'))
                ->icon('heroicon-o-check-badge')
                ->description($this->changeDescription($terbayar, $prevTerbayar, 'Pembayaran'))
                ->descriptionIcon($terbayar >= $prevTerbayar ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($terbayar >= $prevTerbayar ? 'success' : 'warning'),

            Stat::make('Outstanding', 'Rp '.number_format($outstanding, 0, ',', '.'))
                ->icon('heroicon-o-clock')
                ->description('Omset yang belum tertagih pada periode ini')
                ->descriptionIcon('heroicon-m-clock')
                ->color($outstanding > 0 ? 'warning' : 'success'),
        ];
    }

    private function ordersQuery(): Builder
    {
        return UserVisibility::constrainOrdersQuery(Order::query());
    }

    private function countClosings(?string $start, ?string $end): int
    {
        return $this->ordersQuery()
            ->when($start, fn (Builder $query) => $query->whereDate('closing_date', '>=', $start))
            ->when($end, fn (Builder $query) => $query->whereDate('closing_date', '<=', $end))
            ->count();
    }

    private function sumOmset(?string $start, ?string $end): int
    {
        return (int) ($this->ordersQuery()
            ->when($start, fn (Builder $query) => $query->whereDate('closing_date', '>=', $start))
            ->when($end, fn (Builder $query) => $query->whereDate('closing_date', '<=', $end))
            ->selectRaw('SUM(COALESCE(grand_total, total_price + COALESCE(penambahan, 0) - COALESCE(promo, 0) - COALESCE(pengurangan, 0))) as total_omset')
            ->value('total_omset') ?? 0);
    }

    private function sumTerbayar(?string $start, ?string $end): int
    {
        return (int) ($this->ordersQuery()
            ->when($start, fn (Builder $query) => $query->whereDate('closing_date', '>=', $start))
            ->when($end, fn (Builder $query) => $query->whereDate('closing_date', '<=', $end))
            ->join('data_pembayarans', 'orders.id', '=', 'data_pembayarans.order_id')
            ->sum('data_pembayarans.nominal') ?? 0);
    }

    /**
     * @return list<int>
     */
    private function omsetSparkline(): array
    {
        $points = [];

        for ($i = 5; $i >= 0; $i--) {
            $from = now()->subMonthsNoOverflow($i)->startOfMonth()->toDateString();
            $to = now()->subMonthsNoOverflow($i)->endOfMonth()->toDateString();
            $points[] = $this->sumOmset($from, $to);
        }

        return $points;
    }

    private function changeDescription(int|float $current, int|float $previous, string $label): string
    {
        $currentVal = (float) $current;
        $previousVal = (float) $previous;

        if ($previousVal == 0.0) {
            if ($currentVal > 0.0) {
                return $label.' meningkat (periode lalu 0)';
            }

            return $label.' tidak berubah (periode lalu 0)';
        }

        $change = (($currentVal - $previousVal) / abs($previousVal)) * 100;
        $formatted = number_format(abs($change), 1, ',', '.');

        if ($change > 0.0) {
            return $label.' naik '.$formatted.'% dari periode lalu';
        }

        if ($change < 0.0) {
            return $label.' turun '.$formatted.'% dari periode lalu';
        }

        return $label.' tidak berubah dari periode lalu';
    }
}
