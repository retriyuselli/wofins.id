<?php

namespace Database\Seeders;

use App\Models\FixedAsset;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class FixedAssetSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Fixed Assets...');

        $data = [
            [
                'category' => 'EQUIPMENT',
                'asset_name' => 'Peralatan Kantor - Set Meja & Kursi',
                'purchase_date' => Carbon::parse('2026-01-15'),
                'purchase_price' => 25000000,
                'salvage_value' => 3000000,
                'useful_life_years' => 5,
                'useful_life_months' => 0,
                'location' => 'Kantor Pusat Jakarta',
                'condition' => 'GOOD',
                'supplier' => 'PT Furni Jaya',
                'invoice_number' => 'INV-FA-001',
                'warranty_expiry' => Carbon::parse('2028-01-15'),
                'notes' => null,
            ],
            [
                'category' => 'COMPUTER',
                'asset_name' => 'Laptop MacBook Pro 14"',
                'purchase_date' => Carbon::parse('2026-02-01'),
                'purchase_price' => 32000000,
                'salvage_value' => 5000000,
                'useful_life_years' => 4,
                'useful_life_months' => 0,
                'location' => 'Kantor Pusat Jakarta',
                'condition' => 'EXCELLENT',
                'supplier' => 'iBox Indonesia',
                'invoice_number' => 'INV-FA-002',
                'warranty_expiry' => Carbon::parse('2027-02-01'),
                'notes' => 'Untuk tim desain',
            ],
            [
                'category' => 'VEHICLE',
                'asset_name' => 'Mobil Operasional Toyota Avanza',
                'purchase_date' => Carbon::parse('2025-11-10'),
                'purchase_price' => 220000000,
                'salvage_value' => 80000000,
                'useful_life_years' => 8,
                'useful_life_months' => 0,
                'location' => 'Pool Kendaraan',
                'condition' => 'GOOD',
                'supplier' => 'Dealer Toyota',
                'invoice_number' => 'INV-FA-003',
                'warranty_expiry' => Carbon::parse('2028-11-10'),
                'notes' => null,
            ],
            [
                'category' => 'EQUIPMENT',
                'asset_name' => 'Kamera Mirrorless Sony A7 IV',
                'purchase_date' => Carbon::parse('2026-03-05'),
                'purchase_price' => 45000000,
                'salvage_value' => 10000000,
                'useful_life_years' => 5,
                'useful_life_months' => 0,
                'location' => 'Gudang Produksi',
                'condition' => 'EXCELLENT',
                'supplier' => 'Wahanakamera',
                'invoice_number' => 'INV-FA-004',
                'warranty_expiry' => Carbon::parse('2028-03-05'),
                'notes' => null,
            ],
            [
                'category' => 'COMPUTER',
                'asset_name' => 'PC Workstation Editing',
                'purchase_date' => Carbon::parse('2025-12-20'),
                'purchase_price' => 28000000,
                'salvage_value' => 4000000,
                'useful_life_years' => 4,
                'useful_life_months' => 0,
                'location' => 'Studio Editing',
                'condition' => 'GOOD',
                'supplier' => 'Rakitan Pro',
                'invoice_number' => 'INV-FA-005',
                'warranty_expiry' => Carbon::parse('2027-12-20'),
                'notes' => null,
            ],
            [
                'category' => 'FURNITURE',
                'asset_name' => 'Lemari Arsip Metal 4 Pintu',
                'purchase_date' => Carbon::parse('2026-01-28'),
                'purchase_price' => 6500000,
                'salvage_value' => 500000,
                'useful_life_years' => 10,
                'useful_life_months' => 0,
                'location' => 'Ruang Administrasi',
                'condition' => 'GOOD',
                'supplier' => 'Toko Baja Ringan',
                'invoice_number' => 'INV-FA-006',
                'warranty_expiry' => null,
                'notes' => null,
            ],
            [
                'category' => 'EQUIPMENT',
                'asset_name' => 'Sound System Portable',
                'purchase_date' => Carbon::parse('2026-02-18'),
                'purchase_price' => 15000000,
                'salvage_value' => 2000000,
                'useful_life_years' => 5,
                'useful_life_months' => 0,
                'location' => 'Gudang Produksi',
                'condition' => 'GOOD',
                'supplier' => 'AudioPro',
                'invoice_number' => 'INV-FA-007',
                'warranty_expiry' => Carbon::parse('2027-02-18'),
                'notes' => null,
            ],
            [
                'category' => 'COMPUTER',
                'asset_name' => 'Printer Epson L3250',
                'purchase_date' => Carbon::parse('2026-03-12'),
                'purchase_price' => 3200000,
                'salvage_value' => 300000,
                'useful_life_years' => 3,
                'useful_life_months' => 0,
                'location' => 'Ruang Administrasi',
                'condition' => 'EXCELLENT',
                'supplier' => 'Electronic City',
                'invoice_number' => 'INV-FA-008',
                'warranty_expiry' => Carbon::parse('2027-03-12'),
                'notes' => null,
            ],
            [
                'category' => 'VEHICLE',
                'asset_name' => 'Motor Kurir Honda Beat',
                'purchase_date' => Carbon::parse('2025-10-05'),
                'purchase_price' => 18500000,
                'salvage_value' => 5000000,
                'useful_life_years' => 6,
                'useful_life_months' => 0,
                'location' => 'Pool Kendaraan',
                'condition' => 'FAIR',
                'supplier' => 'Dealer Honda',
                'invoice_number' => 'INV-FA-009',
                'warranty_expiry' => Carbon::parse('2028-10-05'),
                'notes' => null,
            ],
            [
                'category' => 'VEHICLE',
                'asset_name' => 'Mobil Box Daihatsu Gran Max',
                'purchase_date' => Carbon::parse('2026-01-08'),
                'purchase_price' => 185000000,
                'salvage_value' => 70000000,
                'useful_life_years' => 8,
                'useful_life_months' => 0,
                'location' => 'Pool Kendaraan',
                'condition' => 'GOOD',
                'supplier' => 'Dealer Daihatsu',
                'invoice_number' => 'INV-FA-010',
                'warranty_expiry' => Carbon::parse('2030-01-15'),
                'notes' => 'Kendaraan angkut barang logistik',
            ],
        ];

        $created = 0;

        foreach ($data as $item) {
            $category = $item['category'];

            $asset = FixedAsset::firstOrCreate(
                [
                    'asset_name' => $item['asset_name'],
                    'purchase_date' => $item['purchase_date'],
                ],
                [
                    'asset_code' => FixedAsset::generateAssetCode($category),
                    'category' => $category,
                    'purchase_price' => $item['purchase_price'],
                    'accumulated_depreciation' => 0,
                    'depreciation_method' => 'STRAIGHT_LINE',
                    'useful_life_years' => $item['useful_life_years'],
                    'useful_life_months' => $item['useful_life_months'],
                    'salvage_value' => $item['salvage_value'],
                    'current_book_value' => $item['purchase_price'],
                    'location' => $item['location'],
                    'condition' => $item['condition'],
                    'supplier' => $item['supplier'],
                    'invoice_number' => $item['invoice_number'],
                    'warranty_expiry' => $item['warranty_expiry'],
                    'notes' => $item['notes'],
                    'is_active' => true,
                ]
            );

            $asset->updateBookValue();
            $created++;
            $this->command->line("- {$asset->asset_name} ({$asset->asset_code})");
        }

        $this->command->info("Created {$created} fixed assets.");
    }
}
