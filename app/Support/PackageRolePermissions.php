<?php

namespace App\Support;

/**
 * Permission Shield untuk role pemilik paket (pengunjung).
 * Permission CRUD untuk role pengunjung (pemilik paket).
 * Modul Nota dinas digating Professional+; Dokumen / HRIS digating Business+.
 * Permission tetap ada agar upgrade tidak perlu re-sync role.
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
