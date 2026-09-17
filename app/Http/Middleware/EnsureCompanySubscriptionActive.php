<?php

namespace App\Http\Middleware;

use App\Support\CompanySubscription;
use App\Support\ProFeatures;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EnsureCompanySubscriptionActive
{
    /**
     * Blokir akses backend Filament jika perusahaan nonaktif atau paket habis.
     * Super admin tetap boleh masuk.
     */
    public function handle(Request $request, Closure $next)
    {
        if (ProFeatures::forceUnlocked() || ProFeatures::actorIsSuperAdmin()) {
            return $next($request);
        }

        $company = CompanySubscription::company();

        if (! $company) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Akun belum terhubung ke perusahaan.'], 403)
                : $this->redirectWithError($request, 'home', 'Akun belum terhubung ke perusahaan.');
        }

        if ($company && $company->isDeactivated()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Perusahaan Anda dinonaktifkan.',
                ], 403);
            }

            return $this->redirectWithError(
                $request,
                'account.company-deactivated',
                'Perusahaan Anda dinonaktifkan. Hubungi admin WOFINS untuk mengaktifkan kembali.',
            );
        }

        if (! CompanySubscription::isExpired()) {
            return $next($request);
        }

        $expiresLabel = CompanySubscription::expiresAtLabel() ?? 'tanggal berakhir';
        $canManage = CompanySubscription::canManageSubscription();

        $message = $canManage
            ? "Masa aktif paket berakhir pada {$expiresLabel}. Perpanjang paket agar seluruh tim kembali mengakses dashboard."
            : "Masa aktif paket perusahaan berakhir pada {$expiresLabel}. Hubungi admin perusahaan Anda untuk perpanjang.";

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 403);
        }

        return $this->redirectWithError($request, 'account.subscription-expired', $message);
    }

    private function redirectWithError(Request $request, string $route, string $message): RedirectResponse
    {
        $request->session()->flash('error', $message);

        return new RedirectResponse(route($route));
    }
}
