<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Rekening bersifat per-company — jangan seed data demo global
     * (nama seperti "Sarah Wijaya" membingungkan seolah milik user lain).
     * Tenant membuat rekening sendiri dari menu Daftar Rekening.
     */
    public function run(): void
    {
        $this->command?->info('⏭ PaymentMethodSeeder dilewati (rekening dibuat per company, bukan demo global).');
    }
}
