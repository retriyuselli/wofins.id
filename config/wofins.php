<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Force Unlock Fitur Paket
    |--------------------------------------------------------------------------
    |
    | Saat true, semua feature gate paket diabaikan (berguna untuk development).
    | Production: biarkan false — akses mengikuti companies.subscription_plan.
    |
    */

    'pro_features_enabled' => (bool) env('WOFINS_PRO_FEATURES', false),

    /*
    |--------------------------------------------------------------------------
    | Default paket jika companies.subscription_plan kosong
    |--------------------------------------------------------------------------
    */

    'default_subscription_plan' => env('WOFINS_DEFAULT_PLAN', 'starter'),

];
