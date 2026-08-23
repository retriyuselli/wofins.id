<?php

namespace App\Providers;

use App\Listeners\CheckUserExpirationOnLogin;
use App\Http\Responses\Filament\LogoutResponse;
use App\Models\BankStatement;
use App\Models\Document;
use App\Models\Order;
use App\Models\User;
use App\Observers\BankStatementObserver;
use App\Observers\DocumentObserver;
use App\Observers\OrderObserver;
use App\Observers\UserObserver;
use App\Support\CompanyBrand;
use App\Support\CompanySubscription;
use App\Support\PricingPlans;
use App\Support\ProFeatures;
use CmsMulti\FilamentClearCache\Facades\FilamentClearCache;
use Filament\Auth\Http\Responses\Contracts\LogoutResponse as LogoutResponseContract;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LogoutResponseContract::class, LogoutResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $hasTable = function (string $table): bool {
            try {
                return Schema::hasTable($table);
            } catch (\Throwable $e) {
                return false;
            }
        };

        $cache = Cache::store();
        if (config('cache.default') === 'database' && ! $hasTable('cache')) {
            $cache = Cache::store('array');
        }

        // Register User Observer
        User::observe(UserObserver::class);

        // Register Order Observer for tracking last edited by
        Order::observe(OrderObserver::class);

        // Register BankStatement Observer for tracking last edited by
        BankStatement::observe(BankStatementObserver::class);

        // Register Document Observer for auto-numbering
        Document::observe(DocumentObserver::class);

        // Register login event listener for daily expiration welcome notifications
        Event::listen(
            Login::class,
            CheckUserExpirationOnLogin::class
        );

        if (env('DB_SLOW_QUERY_LOG', false)) {
            DB::listen(function ($query) {
                $threshold = (int) env('DB_SLOW_MS', 100);
                if ((int) $query->time >= $threshold) {
                    Log::warning('slow_query', [
                        'sql' => $query->sql,
                        'time_ms' => (int) $query->time,
                        'bindings' => $query->bindings,
                    ]);
                }
            });
        }

        View::composer('*', function ($view) {
            $view->with(CompanyBrand::viewData());
        });

        View::composer(['profile.*'], function ($view) {
            $user = auth()->user();
            $adminToolsReadonly = $user
                && method_exists($user, 'hasRole')
                && $user->hasRole('pengunjung')
                && ! $user->hasRole('super_admin');

            // Super admin: portal tidak dikunci paket (boleh pakai semua aksi).
            $proLocked = $user && method_exists($user, 'hasRole') && $user->hasRole('super_admin')
                ? false
                : ProFeatures::locked(PricingPlans::FEATURE_PAYROLL);

            $view->with('proFeatureLocked', $proLocked);
            $view->with('subscriptionPlanLabel', CompanySubscription::planLabel());
            $view->with('subscriptionSeatSummary', CompanySubscription::seatSummary());
            $view->with('adminToolsReadonly', $adminToolsReadonly);

            // Banner akses akun/company di semua halaman portal profil
            if ($user instanceof User && ! $view->offsetExists('accountAccessAlerts')) {
                $alerts = [];
                $user->loadMissing('company');

                if ($user->status === 'terminated') {
                    $alerts[] = [
                        'type' => 'account_terminated',
                        'tone' => 'danger',
                        'title' => 'Akun dinonaktifkan permanen',
                        'body' => 'Status akun Anda Terminated. Akses backend dan login berikutnya diblokir. Hubungi administrator jika ini kesalahan.',
                    ];
                } elseif ($user->status === 'inactive') {
                    $alerts[] = [
                        'type' => 'account_inactive',
                        'tone' => 'warning',
                        'title' => 'Akun sedang nonaktif',
                        'body' => 'Status akun Anda Nonaktif. Akses sementara diblokir. Hubungi administrator untuk mengaktifkan kembali.',
                    ];
                }

                $company = $user->company;
                if ($company && method_exists($company, 'isDeactivated') && $company->isDeactivated() && ! $user->hasRole('super_admin')) {
                    $alerts[] = [
                        'type' => 'company_deactivated',
                        'tone' => 'warning',
                        'title' => 'Perusahaan dinonaktifkan',
                        'body' => 'Perusahaan “'.($company->company_name ?? 'Anda').'” sedang dinonaktifkan. Data tetap tersimpan, tetapi akses dashboard admin ditangguhkan. Hubungi support WOFINS.',
                    ];
                }

                if (CompanySubscription::isExpired() && ! $user->hasRole('super_admin')) {
                    $expiresLabel = CompanySubscription::expiresAtLabel() ?? 'tanggal berakhir';
                    $canManage = CompanySubscription::canManageSubscription($user);
                    $contact = CompanySubscription::subscriptionAdminContact($user);
                    $alerts[] = [
                        'type' => 'subscription_expired',
                        'tone' => 'danger',
                        'title' => 'Masa aktif paket berakhir',
                        'body' => $canManage
                            ? 'Paket '.CompanySubscription::planLabel().' berakhir pada '.$expiresLabel.'. Perpanjang paket agar seluruh tim kembali bisa mengakses backend.'
                            : 'Paket perusahaan berakhir pada '.$expiresLabel.'. Hubungi admin WO ('.$contact['label'].') untuk perpanjang — akses dashboard ditangguhkan untuk seluruh tim.',
                        'can_manage' => $canManage,
                        'admin_email' => $contact['email'],
                    ];
                } elseif (CompanySubscription::isExpiringSoon() && ! $user->hasRole('super_admin')) {
                    $expiresLabel = CompanySubscription::expiresAtLabel() ?? 'segera';
                    $days = CompanySubscription::daysUntilExpiry();
                    $canManage = CompanySubscription::canManageSubscription($user);
                    $contact = CompanySubscription::subscriptionAdminContact($user);
                    $daysText = is_int($days) ? " (sisa {$days} hari)" : '';
                    $alerts[] = [
                        'type' => 'subscription_expiring_soon',
                        'tone' => 'warning',
                        'title' => 'Paket hampir berakhir',
                        'body' => $canManage
                            ? 'Paket '.CompanySubscription::planLabel().' aktif sampai '.$expiresLabel.$daysText.'. Perpanjang sekarang agar tim tidak terdampak.'
                            : 'Paket perusahaan aktif sampai '.$expiresLabel.$daysText.'. Hubungi admin WO ('.$contact['label'].') jika perlu perpanjang.',
                        'can_manage' => $canManage,
                        'admin_email' => $contact['email'],
                    ];
                }

                if (method_exists($user, 'isExpired') && $user->isExpired() && ! in_array($user->status, ['inactive', 'terminated'], true)) {
                    $alerts[] = [
                        'type' => 'account_expired',
                        'tone' => 'danger',
                        'title' => 'Akun kedaluwarsa',
                        'body' => 'Tanggal kedaluwarsa akun sudah lewat. Hubungi administrator untuk perpanjangan.',
                    ];
                }

                $view->with('accountAccessAlerts', $alerts);
            }
        });

        FilamentClearCache::addCommand('optimize:clear');

        // Log Viewer — hanya bisa diakses oleh super_admin
        Gate::define('viewLogViewer', function (?User $user): bool {
            return $user !== null && $user->hasRole('super_admin');
        });

        Gate::after(function ($user, string $ability, $result, array $arguments): ?bool {
            return \App\Support\CompanyRecordAuthorization::after($user, $ability, $result, $arguments);
        });
    }
}
