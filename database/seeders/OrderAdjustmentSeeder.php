<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderPenambahan;
use App\Models\OrderPengurangan;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;

class OrderAdjustmentSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding OrderPenambahan & OrderPengurangan...');

        $orders = Order::query()->where('status', '!=', 'cancelled')->limit(20)->get();
        $vendors = Vendor::all();
        $users = User::all();

        if ($orders->isEmpty()) {
            $this->command->error('No orders found. Run OrderSeeder first.');

            return;
        }

        if ($users->isEmpty()) {
            $this->command->error('No users found. Run UserSeeder first.');

            return;
        }

        $penambahanNames = [
            'Upgrade dekorasi pelaminan premium',
            'Tambahan lighting LED ambient',
            'Extra sound system outdoor',
            'Penambahan dokumentasi drone',
            'Upgrade katering VIP table',
            'Sewa photobooth tambahan',
            'MC bilingual add-on',
            'Floral entrance arch upgrade',
        ];

        $createdAdd = 0;
        $createdReduce = 0;

        foreach ($orders as $index => $order) {
            // Link adjustment context to order's account manager / employee owner
            $owner = $order->user_id
                ? User::find($order->user_id)
                : $users->random();

            $vendor = $vendors->isNotEmpty() ? $vendors->random() : null;
            $hargaPublish = rand(2_000_000, 15_000_000);
            $hargaVendor = (int) round($hargaPublish * (rand(60, 85) / 100));

            OrderPenambahan::query()->firstOrCreate(
                [
                    'order_id' => $order->id,
                    'name' => $penambahanNames[$index % count($penambahanNames)],
                ],
                [
                    'vendor_id' => $vendor?->id,
                    'description' => '<p>Penambahan item untuk order <strong>'.$order->number.'</strong> atas permintaan klien. Dicatat oleh '.($owner?->name ?? 'system').'.</p>',
                    'harga_publish' => $hargaPublish,
                    'harga_vendor' => $hargaVendor,
                ]
            );
            $createdAdd++;

            if ($index % 2 === 0) {
                $pengurangan = rand(500_000, 5_000_000);

                OrderPengurangan::query()->firstOrCreate(
                    [
                        'order_id' => $order->id,
                        'description' => 'Pengurangan paket standar — '.$order->number,
                    ],
                    [
                        'total_pengurangan' => $pengurangan,
                        'notes' => 'Diskon / item dibatalkan. Diproses terkait AM: '.($owner?->name ?? '-').' (user_id: '.($owner?->id ?? '-').')',
                    ]
                );
                $createdReduce++;
            }
        }

        $this->command->info("✅ OrderPenambahan: {$createdAdd}, OrderPengurangan: {$createdReduce}");
    }
}
