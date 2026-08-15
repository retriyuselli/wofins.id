<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectUnauthenticatedToAppUrl
{
    /**
     * Guest yang mengakses panel admin diarahkan ke login frontend
     * (bukan /admin/login).
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        return redirect()->guest(wofins_route('front.login'));
    }
}
