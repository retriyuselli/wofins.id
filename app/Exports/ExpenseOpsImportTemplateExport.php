<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ExpenseOpsImportTemplateExport implements FromArray, WithHeadings, WithTitle
{
    public function headings(): array
    {
        return [
            'name',
            'amount',
            'date_expense',
            'no_nd',
            'note',
        ];
    }

    public function array(): array
    {
        return [
            ['Sewa kantor', 2500000, '2026-08-01', '', 'Contoh baris — hapus sebelum impor'],
        ];
    }

    public function title(): string
    {
        return 'pengeluaran_ops';
    }
}
