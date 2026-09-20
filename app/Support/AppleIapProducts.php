<?php

namespace App\Support;

/**
 * Product IDs In-App Purchase (App Store Connect subscription group: WOFINS Plans).
 *
 * Checklist portal (lakukan di App Store Connect sebelum submit):
 * 1. Agreements, Tax, and Banking → Paid Apps aktif.
 * 2. App → Subscriptions → buat group "WOFINS Plans".
 * 3. Buat 6 Auto-Renewable Subscription dengan Product ID di bawah.
 * 4. Isi lokalization (EN + ID), harga IDR sesuai PricingPlans.
 * 5. Sandbox → Sandbox Testers (akun uji).
 * 6. Users and Access → Keys → App Store Connect API (Issuer ID + Key ID + .p8) opsional untuk Server API.
 * 7. App → App Store Server Notifications V2 URL:
 *    https://app.wofins.id/api/v1/billing/apple/notifications
 */
final class AppleIapProducts
{
    public const BUNDLE_ID = 'id.wofins.app';

    /**
     * @return array<string, array{plan: string, billing: string, label: string, period_label: string}>
     */
    public static function catalog(): array
    {
        return [
            'wofins.starter.monthly' => [
                'plan' => 'starter',
                'billing' => 'monthly',
                'label' => 'Starter',
                'period_label' => 'Bulanan',
            ],
            'wofins.starter.yearly' => [
                'plan' => 'starter',
                'billing' => 'annual',
                'label' => 'Starter',
                'period_label' => 'Tahunan',
            ],
            'wofins.professional.monthly' => [
                'plan' => 'professional',
                'billing' => 'monthly',
                'label' => 'Professional',
                'period_label' => 'Bulanan',
            ],
            'wofins.professional.yearly' => [
                'plan' => 'professional',
                'billing' => 'annual',
                'label' => 'Professional',
                'period_label' => 'Tahunan',
            ],
            'wofins.business.monthly' => [
                'plan' => 'business',
                'billing' => 'monthly',
                'label' => 'Business',
                'period_label' => 'Bulanan',
            ],
            'wofins.business.yearly' => [
                'plan' => 'business',
                'billing' => 'annual',
                'label' => 'Business',
                'period_label' => 'Tahunan',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function productIds(): array
    {
        return array_keys(self::catalog());
    }

    /**
     * @return array{plan: string, billing: string, label: string, period_label: string}|null
     */
    public static function resolve(string $productId): ?array
    {
        return self::catalog()[$productId] ?? null;
    }

    /**
     * Katalog untuk API/mobile (tanpa rahasia).
     *
     * @return list<array<string, mixed>>
     */
    public static function apiCatalog(): array
    {
        $items = [];
        foreach (self::catalog() as $productId => $meta) {
            $plan = PricingPlans::find($meta['plan']);
            $priceKey = $meta['billing'] === 'annual' ? 'price_annual' : 'price_monthly';
            $items[] = [
                'product_id' => $productId,
                'plan' => $meta['plan'],
                'billing' => $meta['billing'],
                'label' => $meta['label'],
                'period_label' => $meta['period_label'],
                'reference_price_idr' => $plan[$priceKey] ?? null,
                'plan_label' => PricingPlans::shortLabel($meta['plan']),
            ];
        }

        return $items;
    }
}
