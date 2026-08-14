<?php

namespace Database\Seeders;

use App\Support\DefaultCategories;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeder.
     * Kategori dibuat per company agar vendor form mengikuti tenant masing-masing.
     * Company baru otomatis dapat kategori lewat Company::created → DefaultCategories.
     */
    public function run(): void
    {
        $result = DefaultCategories::ensureForAllCompanies();

        if ($result['companies'] === 0) {
            $this->command->warn('No companies found — skip CategorySeeder.');

            return;
        }

        $defs = count(DefaultCategories::definitions());
        $this->command->info("{$defs} categories × {$result['companies']} companies ({$result['rows']} rows) upserted.");
    }
}
