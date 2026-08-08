<?php

namespace App\Support;

/**
 * Permission Shield untuk role pemilik paket (pengunjung).
 * CRUD diizinkan di permission; menu/aksi tetap digating PlanResourceGate:
 * - Starter: proyek + keuangan dasar
 * - Professional+: nota dinas, rekonsiliasi, payroll
 * - Business+: dokumen/SOP, HRIS, portal karyawan, dll.
 * Permission modul Pro/Business tetap di-seed agar upgrade tidak perlu re-sync role.
 * Tidak termasuk Role / BankStatement / Leave* / Absensi* (bukan fitur paket pelanggan).
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
            // Dokumen & SOP (gate: Business+)
            'Document',
            'DocumentCategory',
            'Sop',
            'SopCategory',
            'Documentation',
            'DocumentationCategory',
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

        return array_values(array_unique($perms));
    }
}
