<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductPenambahan;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;

class ProductPenambahanSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding ProductPenambahan...');

        $products = Product::query()->limit(30)->get();
        $vendors = Vendor::all();
        $users = User::all();

        if ($products->isEmpty()) {
            $this->command->error('No products found. Run ProductSeeder first.');

            return;
        }

        if ($users->isEmpty()) {
            $this->command->warn('No users found — continuing without user attribution notes.');
        }

        $extras = [
            'Add-on setup ekstra 2 jam',
            'Upgrade material premium',
            'Paket crew tambahan',
            'Transportasi luar kota',
            'Overtime crew malam hari',
            'Custom design request',
        ];

        $created = 0;

        foreach ($products->take(20) as $index => $product) {
            $vendor = $vendors->isNotEmpty() ? $vendors->random() : null;
            $owner = $users->isNotEmpty() ? $users[$index % $users->count()] : null;
            $hargaPublish = rand(500_000, 8_000_000);
            $hargaVendor = (int) round($hargaPublish * (rand(55, 80) / 100));

            ProductPenambahan::query()->firstOrCreate(
                [
                    'product_id' => $product->id,
                    'description' => $extras[$index % count($extras)].' — '.$product->name.($owner ? ' (oleh '.$owner->name.')' : ''),
                ],
                [
                    'vendor_id' => $vendor?->id,
                    'harga_publish' => $hargaPublish,
                    'harga_vendor' => $hargaVendor,
                    'kategori_transaksi' => $index % 4 === 0 ? 'uang_keluar' : 'uang_masuk',
                ]
            );
            $created++;
        }

        $this->command->info("✅ ProductPenambahan: {$created} records linked to products/vendors (attributed to users in description).");
    }
}
