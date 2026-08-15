<?php

namespace App\Filament\Widgets;

use App\Support\CompanySubscription;
use App\Support\UserVisibility;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class SubscriptionQuotaWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Kuota Paket Langganan';

    public static function canView(): bool
    {
        if (! Auth::check()) {
            return false;
        }

        // Kuota bersifat per-company / paket tenant.
        // Super admin tanpa company_id tidak punya konteks paket → jangan tampilkan.
        return CompanySubscription::company() !== null;
    }

    protected function getStats(): array
    {
        $scope = UserVisibility::cacheScopeKey();
        $cacheKey = "dashboard:subscription_quota:{$scope}";

        $payload = Cache::remember($cacheKey, 60, function (): array {
            return [
                'plan' => CompanySubscription::planLabel(),
                'configured' => CompanySubscription::hasConfiguredPlan(),
                'expires_label' => CompanySubscription::expiresAtLabel(),
                'expired' => CompanySubscription::isExpired(),
                'expiring_soon' => CompanySubscription::isExpiringSoon(),
                'days_left' => CompanySubscription::daysUntilExpiry(),
                'rows' => CompanySubscription::quotaMatrix(),
            ];
        });

        $plan = $payload['plan'];
        $configured = $payload['configured'];
        $expiresLabel = $payload['expires_label'];
        $expired = $payload['expired'];
        $expiringSoon = $payload['expiring_soon'];
        $daysLeft = $payload['days_left'];
        $rows = $payload['rows'];

        $planDescription = ! $configured
            ? 'Set paket di Admin → Company agar kuota aktif'
            : ($expiresLabel
                ? ($expired
                    ? 'Berakhir pada '.$expiresLabel.' — akses ditangguhkan'
                    : ($expiringSoon
                        ? 'Aktif sampai '.$expiresLabel.($daysLeft !== null ? " ({$daysLeft} hari lagi)" : '')
                        : 'Aktif sampai '.$expiresLabel))
                : 'Kuota dihitung per tim paket Anda');

        $planColor = ! $configured
            ? 'warning'
            : ($expired ? 'danger' : ($expiringSoon ? 'warning' : 'success'));

        $stats = [
            Stat::make('Paket aktif', $plan)
                ->description($planDescription)
                ->descriptionIcon($configured
                    ? ($expired || $expiringSoon ? 'heroicon-m-clock' : 'heroicon-m-check-badge')
                    : 'heroicon-m-exclamation-triangle')
                ->color($planColor)
                ->icon('heroicon-o-sparkles'),
        ];

        foreach ($rows as $row) {
            $limitLabel = $row['limit'] === null ? '∞' : (string) $row['limit'];
            $description = $row['limit'] === null
                ? 'Tak terbatas'
                : ($row['full']
                    ? 'Kuota penuh — upgrade atau hapus data'
                    : 'Sisa '.$row['remaining']);

            $color = $row['full']
                ? 'danger'
                : ($row['percent'] >= 80 ? 'warning' : 'primary');

            $stats[] = Stat::make($row['label'], $row['used'].' / '.$limitLabel)
                ->description($description)
                ->descriptionIcon($row['full'] ? 'heroicon-m-x-circle' : 'heroicon-m-chart-bar')
                ->color($color)
                ->chart($this->usageSparkline($row['percent']));
        }

        return $stats;
    }

    /**
     * @return list<int>
     */
    private function usageSparkline(int $percent): array
    {
        $filled = (int) max(1, round($percent / 20));

        return array_map(
            fn (int $i) => $i <= $filled ? max(10, $percent) : 4,
            range(1, 5)
        );
    }
}
