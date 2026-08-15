<?php

use App\Support\WofinsHosts;

if (! function_exists('wofins_app_url')) {
    function wofins_app_url(string $path = '/'): string
    {
        return WofinsHosts::appUrl($path);
    }
}

if (! function_exists('wofins_public_url')) {
    function wofins_public_url(string $path = '/'): string
    {
        return WofinsHosts::publicUrl($path);
    }
}

if (! function_exists('wofins_route')) {
    /**
     * Generate named route URL ke host yang tepat (public vs app).
     */
    function wofins_route(string $name, mixed $parameters = [], bool $absolute = true): string
    {
        return WofinsHosts::route($name, $parameters, $absolute);
    }
}
