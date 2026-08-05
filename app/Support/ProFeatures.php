<?php

namespace App\Support;

class ProFeatures
{
    /**
     * Apakah fitur Pro (Absensi, Kompensasi & Cuti, Jadwal) aktif penuh.
     * Saat false, halaman tetap bisa dilihat (preview) tetapi aksi dinonaktifkan.
     */
    public static function enabled(): bool
    {
        return (bool) config('wofins.pro_features_enabled', false);
    }

    public static function locked(): bool
    {
        return ! static::enabled();
    }
}
