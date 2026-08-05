<?php

namespace App\Http\Middleware;

use App\Support\ProFeatures;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProFeature
{
    public function handle(Request $request, Closure $next): Response
    {
        if (ProFeatures::enabled()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Fitur ini hanya tersedia di paket Pro.',
            ], 403);
        }

        return back()->with('error', 'Fitur ini hanya tersedia di paket Pro. Halaman dapat dilihat sebagai pratinjau.');
    }
}
