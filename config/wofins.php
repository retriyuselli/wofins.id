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

    /*
    |--------------------------------------------------------------------------
    | Identitas penyedia (kontrak berlangganan dengan pemilik company)
    |--------------------------------------------------------------------------
    */

    'provider' => [
        'legal_name' => env('WOFINS_PROVIDER_NAME', 'Makna Kreatif Indonesia'),
        'brand' => env('WOFINS_PROVIDER_BRAND', 'WOFINS'),
        'address' => env(
            'WOFINS_PROVIDER_ADDRESS',
            'Jl. Sintraman Jaya I No. 2148, 20 Ilir D II, Kec. Kemuning, Kota Palembang, Sumatera Selatan 30137'
        ),
        'email' => env('WOFINS_PROVIDER_EMAIL', 'office@wofins.id'),
        'support_email' => env('MAIL_SUPPORT_ADDRESS', 'support@wofins.id'),
        'whatsapp' => env('WOFINS_PROVIDER_WHATSAPP', '+62 813-7318-3794'),
        'website' => env('WOFINS_PUBLIC_URL', 'https://wofins.id'),
        'app_url' => env('WOFINS_APP_URL', 'https://app.wofins.id'),
        'signatory_name' => env('WOFINS_PROVIDER_SIGNATORY', 'Kuasa Pengelola WOFINS'),
        'signatory_title' => env('WOFINS_PROVIDER_SIGNATORY_TITLE', 'Penyedia Layanan'),
    ],

    'agreement' => [
        'version' => env('WOFINS_AGREEMENT_VERSION', '2026-09-19'),
        'skip_super_admin' => (bool) env('WOFINS_AGREEMENT_SKIP_SUPER_ADMIN', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Apple In-App Purchase
    |--------------------------------------------------------------------------
    */

    'apple_iap' => [
        'bundle_id' => env('APPLE_IAP_BUNDLE_ID', 'id.wofins.app'),
        // Hanya local/testing: lewati verifikasi tanda tangan JWS (lihat AppleJwsVerifier).
        'skip_verify' => (bool) env('APPLE_IAP_SKIP_VERIFY', false),
    ],

];
