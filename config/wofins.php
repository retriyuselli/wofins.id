<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Fitur Pro
    |--------------------------------------------------------------------------
    |
    | Saat false, menu Absensi / Kompensasi & Cuti / Jadwal & Riwayat tetap
    | bisa dibuka sebagai preview, tetapi aksi (submit, absen, ajukan cuti, dll)
    | dinonaktifkan.
    |
    */

    'pro_features_enabled' => (bool) env('WOFINS_PRO_FEATURES', false),

];
