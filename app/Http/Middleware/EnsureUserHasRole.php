<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * User tanpa role tidak boleh masuk area dashboard/profile karyawan.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->hasAssignedRole()) {
            if ($request->routeIs('account.pending')) {
                return $next($request);
            }

            return redirect()->route('account.pending');
        }

        return $next($request);
    }
}
