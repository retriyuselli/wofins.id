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

    /*
    |--------------------------------------------------------------------------
    | Rekening transfer pesanan paket (manual, belum Midtrans)
    |--------------------------------------------------------------------------
    */

    'checkout_bank' => [
        'bank_name' => env('WOFINS_CHECKOUT_BANK_NAME', env('INVOICE_BANK_NAME', 'Bank BCA')),
        'account_name' => env('WOFINS_CHECKOUT_ACCOUNT_NAME', env('INVOICE_ACCOUNT_NAME', 'Makna Kreatif Indonesia')),
        'account_number' => env('WOFINS_CHECKOUT_ACCOUNT_NUMBER', env('INVOICE_ACCOUNT_NUMBER', '123-4567-890')),
        'notes' => env('WOFINS_CHECKOUT_BANK_NOTES', 'Cantumkan kode pesanan pada berita transfer.'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Host separation (marketing vs customer app)
    |--------------------------------------------------------------------------
    |
    | Production:
    |   WOFINS_APP_HOST=app.wofins.id
    |   WOFINS_PUBLIC_HOSTS=wofins.id,www.wofins.id
    |   APP_URL=https://app.wofins.id
    |   WOFINS_PUBLIC_URL=https://wofins.id
    |
    | Local/dev: biarkan WOFINS_APP_HOST kosong → middleware no-op.
    |
    */

    'app_host' => env('WOFINS_APP_HOST'),

    'public_hosts' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('WOFINS_PUBLIC_HOSTS', ''))
    ))),

    'app_url' => env('WOFINS_APP_URL', env('APP_URL')),

    'public_url' => env('WOFINS_PUBLIC_URL'),

];
