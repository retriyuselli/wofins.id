<?php

namespace App\Support;

/**
 * Permission Shield untuk role pemilik paket (pengunjung).
 * CRUD diizinkan di permission; menu/aksi tetap digating PlanResourceGate:
 * - Starter: proyek + keuangan dasar + crew freelance
 * - Professional+: nota dinas, rekonsiliasi, payroll, simulasi (gate terpisah)
 * - Business+: dokumen/SOP, laporan kinerja AM, dll.
 * Absensi/cuti/portal ESS tidak di-seed (fitur tidak ditawarkan di paket).
 */
class PackageRolePermissions
{
    /**
     * Model Filament yang di-permission-kan ke role pengunjung
     * (CRUD diizinkan; tampilan menu tetap digating PlanResourceGate).
     *
     * @return list<string>
     */
    public static function starterModels(): array
    {
        return [
            // Tim
            'User',
            // Proyek & katalog
            'Order',
            'Prospect',
            'SimulasiProduk',
            'Product',
            'Vendor',
            'Category',
            // Crew freelance (bukan HRIS)
            'DataPribadi',
            // Keuangan dasar
            'Expense',
            'ExpenseOps',
            'PendapatanLain',
            'PengeluaranLain',
            'Piutang',
            'PembayaranPiutang',
            'FixedAsset',
            'PaymentMethod',
            'DataPembayaran',
            // Nota dinas (gate: Professional+)
            'NotaDinas',
            'NotaDinasDetail',
            // Rekonsiliasi (gate: Professional+)
            'BankStatement',
            // Dokumen & SOP (gate: Business+)
            'Document',
            'DocumentCategory',
            'Sop',
            'SopCategory',
            'Documentation',
            'DocumentationCategory',
            // Payroll (gate: Professional+) — tanpa modul absensi/cuti
            'Payroll',
            'Employee',
            'Status',
            // Laporan lanjutan (gate: Business+)
            'AccountManagerTarget',
        ];
    }

    /**
     * @return list<string>
     */
    public static function abilities(): array
    {
        return ['ViewAny', 'View', 'Create', 'Update', 'Delete'];
    }

    /**
     * @return list<string>
     */
    public static function forStarter(): array
    {
        $perms = ['view_products', 'view_orders', 'view_prospects'];

        foreach (static::starterModels() as $model) {
            foreach (static::abilities() as $ability) {
                $perms[] = "{$ability}:{$model}";
            }
        }

        // Company milik sendiri: lihat & ubah profil; buat/hapus hanya super_admin.
        foreach (['ViewAny', 'View', 'Update'] as $ability) {
            $perms[] = "{$ability}:Company";
        }

        return array_values(array_unique($perms));
    }
}
