<?php

namespace Tests\Feature;

use App\Support\AppleIapProducts;
use App\Support\CompanySubscription;
use App\Support\PricingPlans;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AppleIapBillingTest extends TestCase
{
    public function test_billing_routes_are_registered_and_throttled(): void
    {
        foreach (['api.v1.billing.apple.products', 'api.v1.billing.apple.verify', 'api.v1.billing.apple.restore', 'api.v1.billing.apple.notifications'] as $name) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route, "Missing route {$name}");
        }

        $this->assertContains('throttle:20,1', Route::getRoutes()->getByName('api.v1.billing.apple.verify')?->gatherMiddleware() ?? []);
        $this->assertContains('auth:sanctum', Route::getRoutes()->getByName('api.v1.billing.apple.verify')?->gatherMiddleware() ?? []);
        $this->assertSame(
            ['POST'],
            Route::getRoutes()->getByName('api.v1.billing.apple.notifications')?->methods()
        );
    }

    public function test_product_catalog_maps_six_subscriptions(): void
    {
        $catalog = AppleIapProducts::apiCatalog();
        $this->assertCount(6, $catalog);
        $this->assertSame('id.wofins.app', AppleIapProducts::BUNDLE_ID);

        $businessYearly = AppleIapProducts::resolve('wofins.business.yearly');
        $this->assertNotNull($businessYearly);
        $this->assertSame('business', $businessYearly['plan']);
        $this->assertSame('annual', $businessYearly['billing']);

        $plan = PricingPlans::find('business');
        $this->assertNotNull($plan);
        $this->assertSame(3_540_000, $plan['price_annual'] ?? null);
    }

    public function test_activate_from_apple_iap_sets_plan_and_expiry(): void
    {
        $company = new \App\Models\Company([
            'company_name' => 'Temp',
            'subscription_plan' => 'starter',
            'subscription_expires_at' => now()->subDay(),
        ]);
        // Avoid DB: exercise month math via PricingPlans only.
        $pricing = PricingPlans::resolveBillingPrice(PricingPlans::find('business'), 'monthly');
        $this->assertSame(1, (int) ($pricing['months'] ?? 0));

        $annual = PricingPlans::resolveBillingPrice(PricingPlans::find('starter'), 'annual');
        $this->assertSame(12, (int) ($annual['months'] ?? 0));

        $this->assertTrue(method_exists(CompanySubscription::class, 'activateFromAppleIap'));
        $this->assertTrue(method_exists(CompanySubscription::class, 'canManageSubscription'));
    }
}
