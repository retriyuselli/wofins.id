<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class VendorImportTemplateExport implements FromArray, WithHeadings, WithTitle
{
    public function headings(): array
    {
        return [
            'name',
            'phone',
            'category',
            'status',
            'parent',
            'pic_name',
            'address',
            'harga_publish',
            'harga_vendor',
            'stock',
            'bank_name',
            'bank_account',
            'account_holder',
        ];
    }

    public function array(): array
    {
        return [
            [
                'Vendor Dekorasi Induk',
                '81211111111',
                'Dekorasi & Pelaminan',
                'vendor',
                '',
                'Budi',
                'Jakarta',
                0,
                0,
                0,
                'BCA',
                '1234567890',
                'Budi',
            ],
            [
                'Paket Pelaminan A',
                '81222222222',
                'Dekorasi & Pelaminan',
                'product',
                'Vendor Dekorasi Induk',
                'Budi',
                'Jakarta',
                15000000,
                12000000,
                10,
                'BCA',
                '1234567890',
                'Budi',
            ],
        ];
    }

    public function title(): string
    {
        return 'vendors';
    }
}
