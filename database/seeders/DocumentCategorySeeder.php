<?php

namespace Database\Seeders;

use App\Models\DocumentCategory;
use Illuminate\Database\Seeder;

class DocumentCategorySeeder extends Seeder
{
    /**
     * Katalog global: dipakai semua company. Kelola hanya oleh super admin.
     */
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Surat Keputusan',
                'code' => 'SK',
                'type' => 'internal',
                'format_number' => '{SEQ}/{CAT}/{CO}/{ROMAN_MONTH}/{Y}',
                'is_approval_required' => true,
            ],
            [
                'name' => 'Surat Tugas',
                'code' => 'ST',
                'type' => 'internal',
                'format_number' => '{SEQ}/{CAT}/{CO}/{ROMAN_MONTH}/{Y}',
                'is_approval_required' => true,
            ],
            [
                'name' => 'Memo Internal',
                'code' => 'MEMO',
                'type' => 'internal',
                'format_number' => '{SEQ}/{CAT}/{CO}/{ROMAN_MONTH}/{Y}',
                'is_approval_required' => false,
            ],
            [
                'name' => 'Berita Acara',
                'code' => 'BA',
                'type' => 'internal',
                'format_number' => '{SEQ}/{CAT}/{CO}/{ROMAN_MONTH}/{Y}',
                'is_approval_required' => true,
            ],
            [
                'name' => 'Surat Keluar',
                'code' => 'OUT',
                'type' => 'outbound',
                'format_number' => '{SEQ}/{CO}-OUT/{ROMAN_MONTH}/{Y}',
                'is_approval_required' => true,
            ],
            [
                'name' => 'Surat Masuk',
                'code' => 'IN',
                'type' => 'inbound',
                'format_number' => '{SEQ}/{CAT}/{CO}/{ROMAN_MONTH}/{Y}',
                'is_approval_required' => false,
            ],
        ];

        foreach ($templates as $category) {
            DocumentCategory::query()->firstOrCreate(
                ['code' => $category['code']],
                array_merge($category, ['company_id' => null])
            );
        }
    }
}
