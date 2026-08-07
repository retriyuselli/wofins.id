<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminToolsAccess
{
    /**
     * Super admin: akses penuh.
     * Pengunjung: boleh lihat (pratinjau), aksi dinonaktifkan di UI.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! method_exists($user, 'hasRole')) {
            abort(403);
        }

        if ($user->hasRole('super_admin') || $user->hasRole('pengunjung')) {
            return $next($request);
        }

        abort(403);
    }
}
