<?php

namespace App\Http\Middleware;

use App\Support\WofinsHosts;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceHostSeparation
{
    /**
     * Canonical host:
     * - Public host → hanya marketing (+ shared)
     * - App host → login/profile/admin (+ shared); marketing diarahkan ke public
     *
     * Nonaktif jika WOFINS_APP_HOST kosong (local/dev).
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! WofinsHosts::enabled()) {
            return $next($request);
        }

        // Host tidak dikenal (bukan public & bukan app) — biarkan lewat (preview IP, dll.)
        if (! WofinsHosts::isPublicHost($request) && ! WofinsHosts::isAppHost($request)) {
            return $next($request);
        }

        $path = $request->path(); // tanpa leading slash
        $uri = $request->getRequestUri(); // path + query

        if (WofinsHosts::isSharedPath($path)) {
            return $next($request);
        }

        if (WofinsHosts::isPublicHost($request) && WofinsHosts::isAppPath($path)) {
            return redirect()->away(WofinsHosts::appUrl($uri), 302);
        }

        if (WofinsHosts::isAppHost($request) && WofinsHosts::isPublicOnlyPath($path)) {
            return redirect()->away(WofinsHosts::publicUrl($uri), 302);
        }

        return $next($request);
    }
}
