<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WofinsHosts
{
    /**
     * Host separation aktif hanya jika app_host di-set (production).
     * Local/dev: biarkan kosong → semua path di satu host.
     */
    public static function enabled(): bool
    {
        return filled(config('wofins.app_host'));
    }

    public static function appHost(): ?string
    {
        $host = config('wofins.app_host');

        return filled($host) ? strtolower((string) $host) : null;
    }

    /**
     * @return list<string>
     */
    public static function publicHosts(): array
    {
        $hosts = config('wofins.public_hosts', []);
        if (! is_array($hosts)) {
            $hosts = array_filter(array_map('trim', explode(',', (string) $hosts)));
        }

        return array_values(array_unique(array_map(
            static fn ($h) => strtolower((string) $h),
            array_filter($hosts)
        )));
    }

    public static function appBaseUrl(): string
    {
        $configured = rtrim((string) config('wofins.app_url'), '/');
        if ($configured !== '') {
            return $configured;
        }

        $host = self::appHost();
        if ($host) {
            return 'https://'.$host;
        }

        return rtrim((string) config('app.url'), '/');
    }

    public static function publicBaseUrl(): string
    {
        $configured = rtrim((string) config('wofins.public_url'), '/');
        if ($configured !== '') {
            return $configured;
        }

        $hosts = self::publicHosts();
        if ($hosts !== []) {
            // Prefer non-www jika ada
            $primary = collect($hosts)->first(fn ($h) => ! str_starts_with($h, 'www.')) ?? $hosts[0];

            return 'https://'.$primary;
        }

        return rtrim((string) config('app.url'), '/');
    }

    public static function appUrl(string $path = '/'): string
    {
        return self::join(self::appBaseUrl(), $path);
    }

    public static function publicUrl(string $path = '/'): string
    {
        return self::join(self::publicBaseUrl(), $path);
    }

    public static function currentHost(Request $request): string
    {
        return strtolower($request->getHost());
    }

    public static function isAppHost(Request $request): bool
    {
        $app = self::appHost();

        return $app !== null && self::currentHost($request) === $app;
    }

    public static function isPublicHost(Request $request): bool
    {
        $hosts = self::publicHosts();
        if ($hosts === []) {
            return false;
        }

        return in_array(self::currentHost($request), $hosts, true);
    }

    /**
     * Path yang boleh di kedua host (asset, undangan, health).
     */
    public static function isSharedPath(string $path): bool
    {
        $path = self::normalizePath($path);

        if ($path === 'up') {
            return true;
        }

        foreach ([
            'brand',
            'crew',
            'livewire',
            'storage',
            'build',
            'vendor',
            'css',
            'js',
            'fonts',
            'images',
            'favicon',
        ] as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Path customer-app: login, profile, admin, dokumen auth, dll.
     */
    public static function isAppPath(string $path): bool
    {
        $path = self::normalizePath($path);

        if ($path === '') {
            return false;
        }

        $exact = [
            'login',
            'register',
            'logout',
            'forgot-password',
            'keranjang',
            'pesanan-saya',
            'pendaftaran',
            'akun-belum-aktif',
            'paket-berakhir',
            'dashboard',
            'profile',
        ];

        if (in_array($path, $exact, true)) {
            return true;
        }

        $prefixes = [
            'admin',
            'auth',
            'email',
            'reset-password',
            'profile',
            'keranjang',
            'pesanan-saya',
            'simulasi',
            'invoice',
            'payroll',
            'leave-request',
            'leave',
            'hr',
            'data-pribadi',
            'absensi',
            'bank-reconciliation',
            'bank-statement',
            'nota-dinas',
            'laporan',
            'reconciliation',
            'report',
            'reports',
            'prospect',
            'prospect-app',
            'sop',
            'documentation',
            'docs-print',
            'account',
            'akun',
            'filament',
            'livewire-filament',
            '_phpinfo',
        ];

        foreach ($prefixes as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Path marketing-only: dari app host diarahkan ke public host.
     */
    public static function isPublicOnlyPath(string $path): bool
    {
        $path = self::normalizePath($path);

        if ($path === '') {
            return true;
        }

        if (self::isSharedPath($path) || self::isAppPath($path)) {
            return false;
        }

        $prefixes = [
            'fitur',
            'harga',
            'kontak',
            'blog',
            'features',
            'keamanan',
            'tentang-kami',
            'solusi',
            'product',
            'docs',
        ];

        foreach ($prefixes as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                return true;
            }
        }

        return false;
    }

    public static function route(string $name, mixed $parameters = [], bool $absolute = true): string
    {
        $relative = app('url')->route($name, $parameters, false);

        if (! $absolute || ! self::enabled()) {
            return app('url')->route($name, $parameters, $absolute);
        }

        $path = ltrim($relative, '/');

        if (self::isAppPath($path)) {
            return self::appUrl($relative);
        }

        if (self::isPublicOnlyPath($path) || $path === '') {
            return self::publicUrl($relative === '' ? '/' : $relative);
        }

        return app('url')->to($relative);
    }

    protected static function normalizePath(string $path): string
    {
        $path = parse_url($path, PHP_URL_PATH) ?? $path;
        $path = trim((string) $path, '/');

        return $path;
    }

    protected static function join(string $base, string $path): string
    {
        if ($path === '' || $path === '/') {
            return $base.'/';
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        $query = '';
        if (str_contains($path, '?')) {
            [$path, $queryString] = explode('?', $path, 2);
            $query = '?'.$queryString;
        }

        return $base.'/'.ltrim($path, '/').$query;
    }
}
