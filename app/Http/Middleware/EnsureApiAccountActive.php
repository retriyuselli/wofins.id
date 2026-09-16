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
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return $this->deny($request, 'Unauthenticated', 401);
        }

        if (in_array($user->status, ['inactive', 'terminated'], true) || $user->isExpired()) {
            return $this->deny($request, 'Akun Anda tidak aktif. Hubungi administrator.');
        }

        if ($user->hasRole('super_admin')) {
            return $next($request);
        }

        $company = $user->company;
        if (! $company || $company->isDeactivated()) {
            return $this->deny($request, 'Perusahaan Anda tidak aktif. Hubungi administrator.');
        }

        if (CompanySubscription::isExpired($user)) {
            return $this->deny($request, 'Masa aktif paket perusahaan telah berakhir.');
        }

        return $next($request);
    }

    private function deny(Request $request, string $message, int $status = 403): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['message' => $message], $status);
    }
}
