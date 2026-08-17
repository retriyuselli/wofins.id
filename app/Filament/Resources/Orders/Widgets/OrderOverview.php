<?php

namespace App\Filament\Resources\Orders\Widgets;

use App\Enums\OrderStatus;
use App\Filament\Pages\NetCashFlowReport;
use App\Models\DataPembayaran;
use App\Models\Expense;
use App\Models\ExpenseOps;
use App\Models\Order;
use App\Support\Rupiah;
use App\Support\UserVisibility;
use BackedEnum;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;
use Livewire\Attributes\On;

class OrderOverview extends BaseWidget
{
    // protected ?string $pollingInterval = '5s';

    public $metrics = [
        'payments' => 0,
        'projects' => 0,
        'revenue' => 0,
        'processing' => 0,
        'total_revenue' => 0,
        'documents' => 0,
        'pending_documents' => 0,
        'agreement_uploaded' => 0,
        'agreement_pending' => 0,
    ];

    public function mount(): void
    {
        $this->refreshMetrics();
    }

    /**
     * Dengarkan update order dan refresh metrik
     */
    #[On('order-updated')]
    #[On('payment-received')]
    public function refreshMetrics(): void
    {
        $currentMonth = Carbon::now();
        $orders = UserVisibility::constrainOrdersQuery(Order::query());

        // Query tunggal untuk mendapatkan semua metrik bulanan
        $monthlyData = (clone $orders)->whereMonth('closing_date', $currentMonth->month)
            ->whereYear('closing_date', $currentMonth->year)
            ->select(
                DB::raw('COUNT(*) as total_projects'),
                DB::raw('SUM(grand_total) as monthly_revenue'),
                DB::raw('COUNT(CASE WHEN status = "'.OrderStatus::Processing->value.'" THEN 1 END) as processing_count')
            )
            ->first();

        $this->metrics['projects'] = $monthlyData->total_projects ?? 0;
        $this->metrics['revenue'] = $monthlyData->monthly_revenue ?? 0;
        $this->metrics['processing'] = $monthlyData->processing_count ?? 0;
        $this->metrics['documents'] = (clone $orders)->whereNotNull('doc_kontrak')->count();
        $this->metrics['pending_documents'] = (clone $orders)->whereNull('doc_kontrak')->count();
        $this->metrics['agreement_uploaded'] = (clone $orders)->whereNotNull('agreement_product')->count();
        $this->metrics['agreement_pending'] = (clone $orders)->whereNull('agreement_product')->count();

        $processingOrderIds = (clone $orders)
            ->where('status', OrderStatus::Processing->value)
            ->pluck('id');

        $this->metrics['payments'] = DataPembayaran::query()
            ->whereIn('order_id', $processingOrderIds)
            ->sum('nominal');

        $this->metrics['total_revenue'] = (clone $orders)->whereYear('closing_date', $currentMonth->year)
            ->sum('grand_total');

        $this->metrics['total_expenseOps'] = UserVisibility::constrainExpenseOpsQuery(ExpenseOps::query())
            ->sum('amount');

        $this->metrics['total_expense'] = Expense::query()
            ->whereIn('order_id', $processingOrderIds)
            ->sum('amount');
    }

    /**
     * Format mata uang dengan format Rupiah Indonesia
     */
    protected function formatCurrency(float $amount): string
    {
        return Rupiah::format($amount);
    }

    /**
     * Hitung indikator tren sederhana
     */
    protected function calculateTrend(string $metric): array
    {
        $days = 7;
        $startDate = Carbon::now()->subDays($days - 1)->startOfDay();
        $dateKeys = [];
        for ($i = 0; $i < $days; $i++) {
            $dateKeys[] = $startDate->copy()->addDays($i)->toDateString();
        }

        $orders = UserVisibility::constrainOrdersQuery(Order::query());

        if ($metric === 'projects') {
            $rows = (clone $orders)->where('created_at', '>=', $startDate)
                ->selectRaw('DATE(created_at) as d, COUNT(*) as c')
                ->groupBy('d')
                ->orderBy('d', 'asc')
                ->get()
                ->pluck('c', 'd')
                ->all();

            return array_map(fn ($d) => (int) ($rows[$d] ?? 0), $dateKeys);
        }

        if ($metric === 'revenue') {
            $rows = (clone $orders)->where('created_at', '>=', $startDate)
                ->selectRaw('DATE(created_at) as d, SUM(grand_total) as s')
                ->groupBy('d')
                ->orderBy('d', 'asc')
                ->get()
                ->pluck('s', 'd')
                ->all();

            return array_map(fn ($d) => (int) ($rows[$d] ?? 0), $dateKeys);
        }

        return array_fill(0, $days, 0);
    }

    protected function getStats(): array
    {
        // Buat tren sederhana untuk proyek dan pendapatan
        $projectTrend = $this->calculateTrend('projects');
        $revenueTrend = $this->calculateTrend('revenue');
        $statusTarget = OrderStatus::Processing; // Ganti dengan OrderStatus::DONE jika ingin status 'done'
        $targetOrderIds = UserVisibility::constrainOrdersQuery(Order::query())
            ->where('status', $statusTarget)
            ->pluck('id');
        $totalPembayaranUntukTargetOrder = DataPembayaran::whereIn('order_id', $targetOrderIds)
            ->sum('nominal');
        $totalPengeluaranUntukTargetOrder = Expense::whereIn('order_id', $targetOrderIds)
            ->sum('amount');
        $sumUangDiterimaUntukTargetOrder = $totalPembayaranUntukTargetOrder - $totalPengeluaranUntukTargetOrder;

        // Deskripsi bisa disesuaikan berdasarkan statusTarget
        $descriptionText = 'Untuk order dengan status '.($statusTarget instanceof BackedEnum ? $statusTarget->value : $statusTarget);

        return [
            // Ringkasan Pembayaran Pelanggan
            Stat::make('Total Pembayaran Pelanggan', $this->formatCurrency($this->metrics['payments']))
                ->description('Total pembayaran diterima')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->url(\App\Filament\Resources\Orders\OrderResource::getUrl('customer-payments', ['status' => 'all']), true),

            // Ringkasan Pengeluaran Pelanggan
            Stat::make('Total Pengeluaran Pelanggan', $this->formatCurrency($this->metrics['total_expense']))
                ->description('Total pengeluaran')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('danger')
                ->url(\App\Filament\Resources\Orders\OrderResource::getUrl('customer-expenses', ['status' => 'all']), true),

            // File Persetujuan Produk
            Stat::make('File Persetujuan Produk', (string) $this->metrics['agreement_uploaded'])
                ->description('Belum upload: '.$this->metrics['agreement_pending'])
                ->descriptionIcon('heroicon-m-document-check')
                ->color('primary'),

            // Ringkasan Proyek Bulanan
            Stat::make('Proyek Baru Bulan Ini', $this->metrics['projects'])
                ->description('Proyek di '.now()->format('F Y'))
                ->descriptionIcon('heroicon-m-document-plus')
                ->chart($projectTrend)
                ->color('primary')
                ->url(\App\Filament\Resources\Orders\OrderResource::getUrl('view-closing', ['month' => now()->format('Y-m')]), true),

            // Ringkasan Pendapatan Bulanan
            Stat::make('Pendapatan Bulanan', $this->formatCurrency($this->metrics['revenue']))
                ->description('Pendapatan di '.now()->format('F Y'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->chart($revenueTrend)
                ->color('success'),

            // Ringkasan Total Dokumen
            Stat::make('Total Dokumen Kontrak', $this->metrics['documents'])
                ->description('Total dokumen')
                ->description(sprintf('%d dokumen menunggu verifikasi', $this->metrics['pending_documents']))
                ->color('primary'),

            // Ringkasan Total Pendapatan
            Stat::make('Total Pendapatan', $this->formatCurrency($this->metrics['total_revenue']))
                ->description('Pendapatan keseluruhan')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->chart($revenueTrend)
                ->color('success'),

            Stat::make('Total Pengeluaran', $this->formatCurrency($this->metrics['total_expenseOps']))
                ->description('Pengeluaran keseluruhan')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('danger'),

            Stat::make(
                'Total Uang Diterima ('.($statusTarget instanceof BackedEnum ? $statusTarget->value : $statusTarget).')',
                ''.Number::format($sumUangDiterimaUntukTargetOrder, precision: 0, locale: 'id')
            )
                ->description($descriptionText)
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary')
                ->url(
                    \App\Support\ProFeatures::allows(\App\Support\PricingPlans::FEATURE_RECONCILIATION)
                        ? NetCashFlowReport::getUrl(['status' => $statusTarget instanceof BackedEnum ? $statusTarget->value : $statusTarget])
                        : null
                ),
        ];
    }
}
