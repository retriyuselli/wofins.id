<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Company;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $companyId = Company::query()
            ->whereNotNull('subscription_plan')
            ->where('subscription_plan', '!=', '')
            ->orderByDesc('id')
            ->value('id')
            ?? Company::query()->orderBy('id')->value('id');

        $categories = [
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
        ];

        foreach ($categories as $categoryData) {
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
        }

        $this->command->info('10 categories created successfully!');
    }
}
