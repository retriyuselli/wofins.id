<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureApiAccountActive;
use App\Models\Documentation;
use App\Models\User;
use App\Support\HtmlSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    public function test_mobile_api_routes_require_token_ability_and_active_account(): void
    {
        $route = Route::getRoutes()->getByName('api.v1.me');

        $this->assertNotNull($route);
        $middleware = $route->gatherMiddleware();
        $this->assertContains('auth:sanctum', $middleware);
        $this->assertContains('abilities:mobile', $middleware);
        $this->assertContains('api.account.active', $middleware);
        $this->assertSame(CheckAbilities::class, app('router')->getMiddleware()['abilities'] ?? null);
    }

    public function test_apple_login_endpoint_is_throttled_and_uses_native_bundle_audience(): void
    {
        $route = Route::getRoutes()->getByName('api.v1.auth.apple');

        $this->assertNotNull($route);
        $this->assertContains('throttle:10,1', $route->gatherMiddleware());
        $this->assertSame('id.wofins.app', config('services.apple.client_id'));
    }

    public function test_report_exports_are_throttled(): void
    {
        foreach (['api.v1.finance.reports.pdf', 'api.v1.finance.reports.excel'] as $name) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route);
            $this->assertContains('throttle:10,1', $route->gatherMiddleware());
        }

        $this->assertContains(
            'throttle:60,1',
            Route::getRoutes()->getByName('api.v1.finance.projects.widgets')?->gatherMiddleware() ?? []
        );
    }

    public function test_sensitive_web_routes_require_active_subscription_and_plan_feature(): void
    {
        $expectations = [
            'simulasi.show' => 'pro.feature:simulasi',
            'bank-statements.download' => 'pro.feature:reconciliation',
            'reconciliation.auto-match' => 'pro.feature:reconciliation',
            'reconciliation.mark-matched' => 'pro.feature:reconciliation',
            'reconciliation.unmark' => 'pro.feature:reconciliation',
            'account-manager.report.pdf' => 'pro.feature:advanced_reports',
            'profile.compensation' => 'pro.feature:payroll',
        ];

        foreach ($expectations as $name => $featureMiddleware) {
            $route = Route::getRoutes()->getByName($name);

            $this->assertNotNull($route, "Route {$name} tidak ditemukan.");
            $middleware = $route->gatherMiddleware();
            $this->assertContains($featureMiddleware, $middleware);
            $this->assertContains('company.subscription.active', $middleware);
        }
    }

    public function test_reconciliation_rejects_arbitrary_source_tables_before_querying_database(): void
    {
        $this->withoutMiddleware();

        $this->postJson(route('reconciliation.unmark'), [
            'source_id' => 1,
            'source_table' => 'users',
            'bank_item_id' => 1,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('source_table');
    }

    public function test_privacy_policy_is_publicly_accessible(): void
    {
        $this->get('/kebijakan-privasi')
            ->assertOk()
            ->assertSee('Kebijakan Privasi WOFINS')
            ->assertSee('support@wofins.id');
    }

    public function test_terms_of_use_is_publicly_accessible(): void
    {
        $this->get('/syarat-ketentuan')
            ->assertOk()
            ->assertSee('Syarat &amp; Ketentuan Berlangganan WOFINS')
            ->assertSee('support@wofins.id');
    }

    public function test_inactive_api_account_is_rejected(): void
    {
        $user = new User;
        $user->forceFill(['status' => 'inactive']);
        $request = Request::create('/api/v1/me', 'GET');
        $request->setUserResolver(fn () => $user);

        $response = (new EnsureApiAccountActive)->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('Akun Anda tidak aktif.', $response->getData(true)['message']);
    }

    public function test_documentation_content_is_sanitized_on_assignment(): void
    {
        $documentation = new Documentation;
        $documentation->content = '<p onclick="evil()">A</p><script>alert(1)</script><a href="javascript:evil()">B</a>';

        $content = (string) $documentation->content;
        $this->assertStringContainsString('<p>A</p>', $content);
        $this->assertStringNotContainsString('onclick', $content);
        $this->assertStringNotContainsString('script', $content);
        $this->assertStringNotContainsString('javascript:', $content);
    }

    public function test_sanitizer_preserves_safe_rich_text(): void
    {
        $clean = HtmlSanitizer::sanitize('<h2>Judul</h2><p><strong>Aman</strong></p>');

        $this->assertSame('<h2>Judul</h2><p><strong>Aman</strong></p>', $clean);
    }

    public function test_sanctum_tokens_have_a_finite_default_expiration(): void
    {
        $this->assertSame(43200, config('sanctum.expiration'));
        $this->assertSame(30, config('sanctum.mobile_token_expiration_days'));
    }
}
