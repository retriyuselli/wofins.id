<?php

namespace App\Providers\Filament;

use App\Filament\Pages\ProjectDashboard;
use App\Http\Middleware\RedirectUnauthenticatedToAppUrl;
use App\Support\CompanyBrand;
use App\Support\ProFeatures;
use App\Support\WofinsHosts;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use CmsMulti\FilamentClearCache\FilamentClearCachePlugin;
use Filament\Enums\GlobalSearchPosition;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $panel = $panel
            ->globalSearch(position: GlobalSearchPosition::Topbar)
            ->default()
            ->id('admin')
            ->path('admin')
            ->font('Poppins')
            // Login hanya lewat frontend (/login) — jangan sediakan /admin/login
            ->maxContentWidth(Width::Full);

        // Production: panel hanya di app host
        if (WofinsHosts::enabled() && WofinsHosts::appHost()) {
            $panel = $panel->domain(WofinsHosts::appHost());
        }

        return $panel
            ->brandLogo(fn (): string => url('/brand/logo').'?v='.CompanyBrand::version())
            ->brandLogoHeight('2rem')
            ->brandName(fn (): string => CompanyBrand::name())
            ->favicon(fn (): string => url('/brand/favicon').'?v='.CompanyBrand::version())
            ->sidebarCollapsibleOnDesktop(true)
            ->navigationGroups([
                'Penjualan',
                'Kas Proyek',
                'Kas Operasional',
                'Keuangan',
                'SDM',
                'Administrasi',
                'Knowledge Base',
                'Konten',
                'WOFINS',
                'Sistem',
            ])
            ->colors([
                'primary' => Color::Blue,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
            ->pages([
                // Dashboard::class,
                ProjectDashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->renderHook('panels::body.end', fn () => view('filament.inactivity-redirect'))
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                RedirectUnauthenticatedToAppUrl::class,
                Authenticate::class,
                \App\Http\Middleware\EnsureCompanySubscriptionActive::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make()
                    ->navigationLabel('Role')
                    ->navigationGroup('SDM')
                    ->globallySearchable(false)
                    // Menu Role/Shield hanya untuk super_admin platform
                    ->registerNavigation(fn (): bool => ProFeatures::actorIsSuperAdmin()),
                FilamentClearCachePlugin::make(),
            ])
            ->renderHook(
                'panels::topbar.end',
                fn (): string => view('filament.hooks.subscription-plan-badge')->render(),
            );
    }
}
