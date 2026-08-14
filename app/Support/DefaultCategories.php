<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Company;
use Illuminate\Support\Facades\Schema;

class DefaultCategories
{
    /**
     * Master kategori default per company (vendor/produk).
     *
     * @return list<array{name: string, slug: string, is_active: bool}>
     */
    public static function definitions(): array
    {
        return [
            [
                'name' => 'Dekorasi & Pelaminan',
                'slug' => 'dekorasi-pelaminan',
                'is_active' => true,
            ],
            [
                'name' => 'Catering & Makanan',
                'slug' => 'catering-makanan',
                'is_active' => true,
            ],
            [
                'name' => 'Foto & Video',
                'slug' => 'foto-video',
                'is_active' => true,
            ],
            [
                'name' => 'Sound System & Audio',
                'slug' => 'sound-system-audio',
                'is_active' => true,
            ],
            [
                'name' => 'Make Up & Beauty',
                'slug' => 'make-up-beauty',
                'is_active' => true,
            ],
            [
                'name' => 'Transportation',
                'slug' => 'transportation',
                'is_active' => true,
            ],
            [
                'name' => 'Entertainment & MC',
                'slug' => 'entertainment-mc',
                'is_active' => true,
            ],
            [
                'name' => 'Wedding Organizer',
                'slug' => 'wedding-organizer',
                'is_active' => true,
            ],
            [
                'name' => 'Venue & Gedung',
                'slug' => 'venue-gedung',
                'is_active' => true,
            ],
            [
                'name' => 'Undangan & Souvenir',
                'slug' => 'undangan-souvenir',
                'is_active' => true,
            ],
            [
                'name' => 'Lain-lain',
                'slug' => 'lain-lain',
                'is_active' => true,
            ],
        ];
    }

    /**
     * Pastikan company punya kategori default (idempotent).
     */
    public static function ensureForCompany(Company|int $company): int
    {
        if (! Schema::hasTable('categories') || ! Schema::hasColumn('categories', 'company_id')) {
            return 0;
        }

        $companyId = $company instanceof Company ? (int) $company->id : (int) $company;

        if ($companyId <= 0) {
            return 0;
        }

        $count = 0;

        foreach (static::definitions() as $categoryData) {
            Category::withoutGlobalScopes()->updateOrCreate(
                [
                    'company_id' => $companyId,
                    'slug' => $categoryData['slug'],
                ],
                [
                    'name' => $categoryData['name'],
                    'is_active' => $categoryData['is_active'],
                    'company_id' => $companyId,
                ]
            );
            $count++;
        }

        return $count;
    }

    /**
     * Seed semua company yang ada.
     *
     * @return array{companies: int, rows: int}
     */
    public static function ensureForAllCompanies(): array
    {
        $companyIds = Company::query()->orderBy('id')->pluck('id')->all();
        $rows = 0;

        foreach ($companyIds as $companyId) {
            $rows += static::ensureForCompany((int) $companyId);
        }

        return [
            'companies' => count($companyIds),
            'rows' => $rows,
        ];
    }
}
