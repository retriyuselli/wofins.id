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

];
