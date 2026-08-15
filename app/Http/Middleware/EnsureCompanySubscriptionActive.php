<?php

namespace App\Http\Middleware;

use App\Support\CompanySubscription;
use App\Support\ProFeatures;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanySubscriptionActive
{
/**
     * Blokir akses backend Filament jika perusahaan nonaktif atau paket habis.
     * Super admin tetap boleh masuk.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (ProFeatures::forceUnlocked() || ProFeatures::actorIsSuperAdmin()) {
            return $next($request);
        }

        $company = CompanySubscription::company();

        if ($company && $company->isDeactivated()) {
            return redirect()
                ->route('account.company-deactivated')
                ->with('error', 'Perusahaan Anda dinonaktifkan. Hubungi admin WOFINS untuk mengaktifkan kembali.');
        }

        if (! CompanySubscription::isExpired()) {
            return $next($request);
        }

        $expiresLabel = CompanySubscription::expiresAtLabel() ?? 'tanggal berakhir';

        return redirect()
            ->route('account.subscription-expired')
            ->with('error', "Masa aktif paket Anda berakhir pada {$expiresLabel}. Perpanjang paket untuk kembali mengakses dashboard.");
    }
}
