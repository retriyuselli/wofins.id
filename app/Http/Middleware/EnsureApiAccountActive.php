<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\CompanySubscription;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiAccountActive
{
    /**
     * Endpoint yang tetap boleh diakses saat paket company expired
     * (agar iOS bisa menampilkan layar perpanjang tanpa memutus sesi).
     *
     * @var list<string>
     */
    private array $allowedWhenSubscriptionExpired = [
        'api/v1/me',
        'api/v1/auth/logout',
        'api/v1/billing/apple/*',
    ];

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return $this->deny($request, 'Unauthenticated', 401, revokeToken: true);
        }

        if (in_array($user->status, ['inactive', 'terminated'], true) || $user->isExpired()) {
            return $this->deny($request, 'Akun Anda tidak aktif.', revokeToken: true);
        }

        if ($user->hasRole('super_admin')) {
            return $next($request);
        }

        $company = $user->company;
        if (! $company) {
            // Akun pending (belum terhubung company): izinkan /me + logout saja.
            if ($this->allowsWhenSubscriptionExpired($request)) {
                return $next($request);
            }

            return $this->deny(
                $request,
                'Akun belum terhubung ke perusahaan. Fitur dashboard belum tersedia.',
                status: 403,
                code: 'company_pending',
                revokeToken: false,
            );
        }

        if ($company->isDeactivated()) {
            return $this->deny($request, 'Perusahaan Anda tidak aktif.', revokeToken: true);
        }

        if (CompanySubscription::isExpired($user)) {
            if ($this->allowsWhenSubscriptionExpired($request)) {
                return $next($request);
            }

            $expiresLabel = CompanySubscription::expiresAtLabel($user) ?? 'tanggal berakhir';

            return $this->deny(
                $request,
                "Masa aktif paket perusahaan telah berakhir (hingga {$expiresLabel}). Akses dashboard sementara ditangguhkan.",
                status: 403,
                code: 'subscription_expired',
                revokeToken: false,
            );
        }

        return $next($request);
    }

    private function allowsWhenSubscriptionExpired(Request $request): bool
    {
        foreach ($this->allowedWhenSubscriptionExpired as $path) {
            if ($request->is($path)) {
                // Hanya GET /me yang diizinkan; PATCH/POST profil tetap diblokir.
                if ($path === 'api/v1/me') {
                    return $request->isMethod('GET');
                }

                return true;
            }
        }

        return false;
    }

    private function deny(
        Request $request,
        string $message,
        int $status = 403,
        ?string $code = null,
        bool $revokeToken = true,
    ): JsonResponse {
        if ($revokeToken) {
            $request->user()?->currentAccessToken()?->delete();
        }

        $payload = ['message' => $message];
        if ($code !== null) {
            $payload['code'] = $code;
        }

        return response()->json($payload, $status);
    }
}
