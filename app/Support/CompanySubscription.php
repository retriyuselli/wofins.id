<?php

namespace App\Support;

use App\Models\Company;
use App\Models\Order;
use App\Models\Product;
use App\Models\Prospect;
use App\Models\SimulasiProduk;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class CompanySubscription
{
    public const DEFAULT_PLAN = 'starter';

    public const RESOURCE_USERS = 'users';

    public const RESOURCE_VENDORS = 'vendors';

    public const RESOURCE_PRODUCTS = 'products';

    public const RESOURCE_ORDERS = 'orders';

    public const RESOURCE_PROSPECTS = 'prospects';

    public const RESOURCE_SIMULASI = 'simulasi';

    /**
     * @return list<string>
     */
    public static function quotaResources(): array
    {
        return [
            self::RESOURCE_USERS,
            self::RESOURCE_VENDORS,
            self::RESOURCE_PRODUCTS,
            self::RESOURCE_ORDERS,
            self::RESOURCE_PROSPECTS,
            self::RESOURCE_SIMULASI,
        ];
    }

    public static function company(): ?Company
    {
        if (! Schema::hasTable('companies')) {
            return null;
        }

        return Cache::remember('wofins.company.subscription', 60, function () {
            return Company::query()->latest('id')->first();
        });
    }

    public static function forgetCache(): void
    {
        Cache::forget('wofins.company.subscription');
    }

    /**
     * Apakah perusahaan sudah memilih paket Starter / Professional / Business.
     */
    public static function hasConfiguredPlan(): bool
    {
        $key = static::company()?->subscription_plan;

        return PricingPlans::find($key) !== null;
    }

    public static function planKey(): string
    {
        $company = static::company();
        $key = $company?->subscription_plan;

        if (PricingPlans::find($key)) {
            return $key;
        }

        // Nilai lama "enterprise" atau kosong: belum dikonfigurasi di app ini
        return (string) config('wofins.default_subscription_plan', self::DEFAULT_PLAN);
    }

    public static function plan(): array
    {
        return PricingPlans::find(static::planKey()) ?? PricingPlans::find(self::DEFAULT_PLAN);
    }

    public static function planLabel(): string
    {
        if (! static::hasConfiguredPlan()) {
            $raw = static::company()?->subscription_plan;
            if ($raw === 'enterprise') {
                return 'Enterprise (produk terpisah) — belum dipetakan';
            }

            return 'Paket belum diatur';
        }

        return PricingPlans::shortLabel(static::planKey());
    }

    /**
     * Batas efektif untuk resource (override perusahaan menang).
     * null = tak terbatas.
     *
     * Jika paket belum diatur / masih "enterprise" lama: tidak dibatasi
     * sampai admin memilih Starter / Professional / Business.
     */
    public static function limit(string $resource): ?int
    {
        if (! static::hasConfiguredPlan()) {
            return null;
        }

        $company = static::company();
        $override = match ($resource) {
            self::RESOURCE_USERS, 'seats' => $company?->seat_limit_override,
            self::RESOURCE_VENDORS => $company?->vendor_limit_override,
            self::RESOURCE_PRODUCTS => $company?->product_limit_override,
            self::RESOURCE_ORDERS => $company?->order_limit_override,
            self::RESOURCE_PROSPECTS => $company?->prospect_limit_override,
            self::RESOURCE_SIMULASI, 'simulations' => $company?->simulasi_limit_override,
            default => null,
        };

        if ($override !== null) {
            return max(0, (int) $override);
        }

        $normalized = match ($resource) {
            'seats' => self::RESOURCE_USERS,
            'simulations' => self::RESOURCE_SIMULASI,
            default => $resource,
        };

        return PricingPlans::limit(static::planKey(), $normalized);
    }

    public static function used(string $resource): int
    {
        return match ($resource) {
            self::RESOURCE_USERS, 'seats' => static::seatsUsed(),
            self::RESOURCE_VENDORS => static::vendorsUsed(),
            self::RESOURCE_PRODUCTS => static::productsUsed(),
            self::RESOURCE_ORDERS => static::ordersUsed(),
            self::RESOURCE_PROSPECTS => static::prospectsUsed(),
            self::RESOURCE_SIMULASI, 'simulations' => static::simulasiUsed(),
            default => 0,
        };
    }

    public static function remaining(string $resource): ?int
    {
        $limit = static::limit($resource);

        if ($limit === null) {
            return null;
        }

        return max(0, $limit - static::used($resource));
    }

    public static function canCreate(string $resource): bool
    {
        // Super admin platform boleh melebihi kuota paket (override operasional).
        if (ProFeatures::actorIsSuperAdmin()) {
            return true;
        }

        $remaining = static::remaining($resource);

        return $remaining === null || $remaining > 0;
    }

    public static function resourceLabel(string $resource): string
    {
        return match ($resource) {
            self::RESOURCE_USERS, 'seats' => 'pengguna',
            self::RESOURCE_VENDORS => 'vendor',
            self::RESOURCE_PRODUCTS => 'produk',
            self::RESOURCE_ORDERS => 'proyek wedding',
            self::RESOURCE_PROSPECTS => 'prospek',
            self::RESOURCE_SIMULASI, 'simulations' => 'simulasi',
            default => $resource,
        };
    }

    public static function summary(string $resource): string
    {
        $used = static::used($resource);
        $limit = static::limit($resource);
        $label = static::resourceLabel($resource);

        if ($limit === null) {
            return "{$used} {$label} (tak terbatas)";
        }

        return "{$used} / {$limit} {$label}";
    }

    public static function fullMessage(string $resource): string
    {
        $plan = static::planLabel();
        $limit = static::limit($resource);
        $label = static::resourceLabel($resource);

        if ($limit === null) {
            return "Kuota {$label} penuh.";
        }

        return "Kuota {$plan} ({$limit} {$label}) sudah penuh. Upgrade paket atau hapus data yang tidak dipakai.";
    }

    public static function quotasOverview(): string
    {
        return implode(' · ', array_map(
            fn (string $resource) => static::summary($resource),
            static::quotaResources()
        ));
    }

    /**
     * Matriks kuota untuk dashboard / profil.
     *
     * @return list<array{key: string, label: string, used: int, limit: int|null, remaining: int|null, percent: int, summary: string, full: bool}>
     */
    public static function quotaMatrix(): array
    {
        $labels = [
            self::RESOURCE_USERS => 'Pengguna',
            self::RESOURCE_VENDORS => 'Vendor',
            self::RESOURCE_PRODUCTS => 'Produk',
            self::RESOURCE_ORDERS => 'Proyek Wedding',
            self::RESOURCE_PROSPECTS => 'Prospek',
            self::RESOURCE_SIMULASI => 'Simulasi',
        ];

        $rows = [];

        foreach ($labels as $key => $label) {
            $used = static::used($key);
            $limit = static::limit($key);
            $remaining = static::remaining($key);
            $percent = $limit === null
                ? 0
                : (int) min(100, round(($used / max(1, $limit)) * 100));

            $rows[] = [
                'key' => $key,
                'label' => $label,
                'used' => $used,
                'limit' => $limit,
                'remaining' => $remaining,
                'percent' => $percent,
                'summary' => static::summary($key),
                'full' => ! static::canCreate($key),
            ];
        }

        return $rows;
    }

    public static function seatLimit(): ?int
    {
        return static::limit(self::RESOURCE_USERS);
    }

    /**
     * Kursi terpakai.
     * - super_admin / agregat platform: semua user ber-role jabatan (exclude pure platform logic)
     * - pemilik paket: hanya tim (root + created_by = root)
     */
    public static function seatsUsed(): int
    {
        if (! Schema::hasTable('users')) {
            return 0;
        }

        $query = User::query()
            ->where(function ($q) {
                $q->whereNull('status')
                    ->orWhere('status', '!=', 'terminated');
            })
            ->whereHas('roles', function ($q) {
                $q->where('name', '!=', 'super_admin');
            });

        if (! ProFeatures::actorIsSuperAdmin()) {
            UserVisibility::constrainUsersQuery($query);
        }

        return $query->count();
    }

    public static function vendorsUsed(): int
    {
        if (! Schema::hasTable('vendors')) {
            return 0;
        }

        $query = Vendor::query();
        static::applyTeamOwnerScope($query, 'created_by');

        return $query->count();
    }

    public static function productsUsed(): int
    {
        if (! Schema::hasTable('products')) {
            return 0;
        }

        $query = Product::query();
        static::applyTeamOwnerScope($query, 'created_by');

        return $query->count();
    }

    public static function ordersUsed(): int
    {
        if (! Schema::hasTable('orders')) {
            return 0;
        }

        $query = Order::query();
        static::applyTeamOwnerScope($query, 'user_id');

        return $query->count();
    }

    public static function prospectsUsed(): int
    {
        if (! Schema::hasTable('prospects')) {
            return 0;
        }

        $query = Prospect::query();
        static::applyTeamOwnerScope($query, 'user_id');

        return $query->count();
    }

    public static function simulasiUsed(): int
    {
        if (! Schema::hasTable('simulasi_produks')) {
            return 0;
        }

        $query = SimulasiProduk::query();
        static::applyTeamOwnerScope($query, 'user_id');

        return $query->count();
    }

    /**
     * Kuota selalu dihitung per tim untuk non–super_admin
     * (bukan lewat actorSeesGlobalAggregates / finance).
     *
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     */
    private static function applyTeamOwnerScope(Builder $query, string $column): void
    {
        if (ProFeatures::actorIsSuperAdmin()) {
            return;
        }

        $teamIds = UserVisibility::teamUserIds();

        if ($teamIds === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereIn($column, $teamIds);
    }

    public static function seatsRemaining(): ?int
    {
        return static::remaining(self::RESOURCE_USERS);
    }

    public static function hasSeatAvailable(): bool
    {
        return static::canCreate(self::RESOURCE_USERS);
    }

    public static function seatSummary(): string
    {
        return static::summary(self::RESOURCE_USERS);
    }

    public static function allows(string $feature): bool
    {
        // Belum ada paket Starter/Pro/Business: jangan kunci fitur (instalasi lama)
        if (! static::hasConfiguredPlan()) {
            return true;
        }

        return PricingPlans::allows(static::planKey(), $feature);
    }

    /**
     * Paket di-set manual di Filament → Company.
     * Method ini sengaja no-op agar Approve / prospect tidak menimpa paket.
     * ProspectApp.service tetap disimpan sebagai minat pendaftar (sales note).
     *
     * @deprecated Gunakan Company form / update companies.subscription_plan.
     */
    public static function syncPlanFromService(?string $service, bool $overwrite = false): void
    {
        // Manual-only policy: jangan sync otomatis dari service prospect.
    }

    public static function upgradeMessage(string $feature = PricingPlans::FEATURE_HRIS): string
    {
        $plan = static::planLabel();

        $businessOnly = [
            PricingPlans::FEATURE_DOCUMENTS,
            PricingPlans::FEATURE_HRIS,
            PricingPlans::FEATURE_EMPLOYEE_PORTAL,
            PricingPlans::FEATURE_ADVANCED_REPORTS,
            PricingPlans::FEATURE_MULTI_APPROVAL,
            PricingPlans::FEATURE_ROLE_MANAGEMENT,
        ];

        $target = in_array($feature, $businessOnly, true)
            ? 'Business'
            : 'Professional atau Business';

        return "Fitur ini tidak termasuk {$plan}. Upgrade ke {$target} untuk membuka akses.";
    }

    public static function seatFullMessage(): string
    {
        return static::fullMessage(self::RESOURCE_USERS);
    }
}
