<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Company;
use App\Models\DataPembayaran;
use App\Models\Expense;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductPengurangan;
use App\Models\ProductVendor;
use App\Models\Prospect;
use App\Models\User;
use App\Models\Vendor;
use App\Support\DefaultCategories;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Demo data untuk tenant Ramadhona Utama WO
 * (user ramadhona.rdpro@hotmail.com / company_id milik user itu).
 *
 * php artisan db:seed --class=RamadhonaStarterDemoSeeder
 */
class RamadhonaStarterDemoSeeder extends Seeder
{
    private const OWNER_EMAIL = 'ramadhona.rdpro@hotmail.com';

    public function run(): void
    {
        $user = User::query()->where('email', self::OWNER_EMAIL)->first();

        if (! $user) {
            $this->command->error('User '.self::OWNER_EMAIL.' tidak ditemukan.');

            return;
        }

        if (! $user->company_id) {
            $this->command->error('User '.self::OWNER_EMAIL.' belum terhubung ke company.');

            return;
        }

        $company = Company::query()->find($user->company_id);

        if (! $company) {
            $this->command->error("Company #{$user->company_id} tidak ditemukan.");

            return;
        }

        $this->command->info("Seeding demo untuk {$user->name} / {$company->company_name} (company_id={$company->id})");

        DefaultCategories::ensureForCompany($company);

        $categories = Category::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->get()
            ->keyBy('slug');

        if ($categories->isEmpty()) {
            $this->command->error('Kategori company kosong. Jalankan CategorySeeder dulu.');

            return;
        }

        DB::transaction(function () use ($user, $company, $categories) {
            $vendors = $this->seedVendors($user, $company, $categories);
            $products = $this->seedProducts($user, $company, $categories, $vendors);
            $rekening = $this->seedRekening($company);
            $prospects = $this->seedProspects($user, $company);
            $this->seedOrders($user, $company, $prospects, $products, $vendors, $rekening);
        });

        $cid = $company->id;
        $this->command->info('Selesai. Ringkasan company ini:');
        $this->command->table(
            ['Data', 'Jumlah'],
            [
                ['Vendor', Vendor::withoutGlobalScopes()->where('company_id', $cid)->count()],
                ['Produk', Product::withoutGlobalScopes()->where('company_id', $cid)->count()],
                ['Daftar Rekening', PaymentMethod::withoutGlobalScopes()->where('company_id', $cid)->count()],
                ['Prospek', Prospect::withoutGlobalScopes()->where('company_id', $cid)->count()],
                ['Proyek Wedding', Order::withoutGlobalScopes()->where('company_id', $cid)->count()],
            ]
        );
    }

    /**
     * @return \Illuminate\Support\Collection<string, Vendor>
     */
    private function seedVendors(User $user, Company $company, $categories)
    {
        $created = 0;

        foreach ($this->vendorDefinitions() as $row) {
            $category = $categories->get($row['category']);
            if (! $category) {
                $category = $categories->get('lain-lain') ?? $categories->first();
            }

            $slug = 'ruwo-'.Str::slug($row['name']);
            $hargaPublish = $row['harga_publish'];
            $hargaVendor = $row['harga_vendor'];

            $vendor = Vendor::withoutGlobalScopes()->firstOrCreate(
                [
                    'company_id' => $company->id,
                    'slug' => $slug,
                ],
                [
                    'created_by' => $user->id,
                    'name' => $row['name'],
                    'category_id' => $category->id,
                    'status' => 'vendor',
                    'is_master' => false,
                    'is_published' => true,
                    'pic_name' => $row['pic'],
                    'phone' => $row['phone'],
                    'address' => $row['address'],
                    'description' => '<p>'.$row['description'].'</p>',
                    'harga_publish' => $hargaPublish,
                    'harga_vendor' => $hargaVendor,
                    'bank_name' => $row['bank'],
                    'bank_account' => $row['account'],
                    'account_holder' => $row['holder'],
                ]
            );

            if ($vendor->wasRecentlyCreated) {
                $created++;
            }
        }

        $this->command->info("Vendor: {$created} baru (target 30).");

        return Vendor::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('slug', 'like', 'ruwo-%')
            ->get()
            ->keyBy('slug');
    }

    /**
     * @param  \Illuminate\Support\Collection<string, Vendor>  $vendors
     * @return \Illuminate\Support\Collection<int, Product>
     */
    private function seedProducts(User $user, Company $company, $categories, $vendors)
    {
        $created = 0;
        $products = collect();

        foreach ($this->productDefinitions() as $row) {
            $category = $categories->get($row['category']) ?? $categories->first();
            $slug = 'ruwo-'.Str::slug($row['name']);

            $product = Product::withoutGlobalScopes()->firstOrCreate(
                [
                    'company_id' => $company->id,
                    'slug' => $slug,
                ],
                [
                    'created_by' => $user->id,
                    'name' => $row['name'],
                    'category_id' => $category->id,
                    'pax' => $row['pax'],
                    'stock' => 10,
                    'is_active' => true,
                    'is_approved' => true,
                    'product_price' => 0,
                    'pengurangan' => 0,
                    'price' => 0,
                    'description' => '<p>'.$row['description'].'</p>',
                ]
            );

            if ($product->wasRecentlyCreated) {
                $created++;
            }

            $totalPublish = 0;
            foreach ($row['vendors'] as $vendorSlug) {
                $vendor = $vendors->get($vendorSlug);
                if (! $vendor) {
                    continue;
                }

                $qty = 1;
                $pricePublic = (int) $vendor->harga_publish * $qty;
                $priceVendor = (int) $vendor->harga_vendor * $qty;
                $totalPublish += $pricePublic;

                ProductVendor::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'vendor_id' => $vendor->id,
                    ],
                    [
                        'harga_publish' => $vendor->harga_publish,
                        'harga_vendor' => $vendor->harga_vendor,
                        'quantity' => $qty,
                        'price_public' => $pricePublic,
                        'total_price' => $priceVendor,
                        'description' => '<p>Termasuk layanan '.$vendor->name.'.</p>',
                    ]
                );
            }

            $pengurangan = (int) ($row['pengurangan'] ?? 0);
            if ($pengurangan > 0) {
                ProductPengurangan::firstOrCreate(
                    [
                        'product_id' => $product->id,
                        'description' => 'Diskon paket '.$row['name'],
                    ],
                    [
                        'amount' => $pengurangan,
                        'notes' => '<p>Diskon seeder paket Ramadhona Utama WO.</p>',
                    ]
                );
            }

            $product->update([
                'product_price' => $totalPublish,
                'pengurangan' => $pengurangan,
                'price' => max(0, $totalPublish - $pengurangan),
            ]);

            $products->push($product->fresh());
        }

        $this->command->info("Produk: {$created} baru (target 10).");

        return $products->values();
    }

    private function seedRekening(Company $company): PaymentMethod
    {
        $existing = PaymentMethod::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->first();

        if ($existing) {
            $this->command->info("Daftar Rekening: memakai yang sudah ada ({$existing->name} / {$existing->bank_name}).");

            return $existing;
        }

        $rekening = PaymentMethod::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => $company->owner_name ?: 'Ramadhona Utama',
            'bank_name' => 'BCA',
            'cabang' => 'Jakarta',
            'no_rekening' => '0213298906',
            'is_cash' => false,
            'opening_balance' => 0,
            'opening_balance_date' => null,
        ]);

        $this->command->info('Daftar Rekening: 1 baru.');

        return $rekening;
    }

    /**
     * @return \Illuminate\Support\Collection<int, Prospect>
     */
    private function seedProspects(User $user, Company $company)
    {
        $created = 0;
        $prospects = collect();

        foreach ($this->prospectDefinitions() as $index => $row) {
            $base = Carbon::now()->addMonths($index + 1)->startOfMonth()->addDays(5 + $index);

            $prospect = Prospect::withoutGlobalScopes()->firstOrCreate(
                [
                    'company_id' => $company->id,
                    'name_event' => $row['name_event'],
                ],
                [
                    'name_cpp' => $row['cpp'],
                    'name_cpw' => $row['cpw'],
                    'date_lamaran' => $base->copy()->subMonths(2)->toDateString(),
                    'date_akad' => $base->toDateString(),
                    'date_resepsi' => $base->copy()->addDay()->toDateString(),
                    'venue' => $row['venue'],
                    'phone' => $row['phone'],
                    'address' => $row['address'],
                    'total_penawaran' => $row['penawaran'],
                    'notes' => $row['notes'],
                    'user_id' => $user->id,
                ]
            );

            if ($prospect->wasRecentlyCreated) {
                $created++;
            }

            $prospects->push($prospect);
        }

        $this->command->info("Prospek: {$created} baru (target 10).");

        return $prospects->values();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Prospect>  $prospects
     * @param  \Illuminate\Support\Collection<int, Product>  $products
     * @param  \Illuminate\Support\Collection<string, Vendor>  $vendors
     */
    private function seedOrders(
        User $user,
        Company $company,
        $prospects,
        $products,
        $vendors,
        PaymentMethod $rekening
    ): void {
        $statuses = ['processing', 'pending', 'done', 'processing', 'pending', 'done', 'processing', 'pending', 'done', 'cancelled'];
        $prefix = $company->woInitial();
        $created = 0;
        $vendorList = $vendors->values();

        foreach ($prospects as $index => $prospect) {
            $product = $products->get($index) ?? $products->first();
            if (! $product) {
                break;
            }

            $number = sprintf('%s-%06d', $prefix, 200001 + $index);
            $status = $statuses[$index] ?? 'pending';
            $createdAt = Carbon::now()->subMonths(8 - $index)->addDays($index * 2)->setTime(10, 0);

            $order = Order::withoutGlobalScopes()->firstOrCreate(
                [
                    'company_id' => $company->id,
                    'number' => $number,
                ],
                [
                    'prospect_id' => $prospect->id,
                    'name' => $prospect->name_event,
                    'slug' => Str::slug($prospect->name_event.'-'.$number),
                    'user_id' => $user->id,
                    'employee_id' => $user->id,
                    'no_kontrak' => 'KONTR-'.$prefix.'-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                    'pax' => (int) $product->pax,
                    'status' => $status,
                    'total_price' => (int) $product->price,
                    'grand_total' => (int) $product->price,
                    'paid_amount' => 0,
                    'note' => '<p>Proyek wedding '.$prospect->name_event.' untuk '.$company->company_name.'.</p>',
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]
            );

            if ($order->wasRecentlyCreated) {
                $created++;
            }

            OrderProduct::firstOrCreate(
                [
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                ],
                [
                    'quantity' => 1,
                    'unit_price' => (int) $product->price,
                ]
            );

            $order->update([
                'total_price' => (int) $product->price,
                'grand_total' => (int) $product->price,
            ]);

            if ($status === 'cancelled') {
                continue;
            }

            $dp = (int) round(((int) $product->price) * 0.30);
            $pelunasan = $status === 'done' ? ((int) $product->price) - $dp : 0;

            $this->ensurePayment($order, $company, $rekening, $dp, $createdAt->copy()->addDays(7), 'DP '.$order->name);

            if ($pelunasan > 0) {
                $this->ensurePayment($order, $company, $rekening, $pelunasan, $createdAt->copy()->addDays(40), 'Pelunasan '.$order->name);
            }

            $paid = (int) DataPembayaran::withoutGlobalScopes()
                ->where('order_id', $order->id)
                ->sum('nominal');

            $order->update([
                'paid_amount' => $paid,
                'closing_date' => $createdAt->copy()->addDays(7)->toDateString(),
                'is_paid' => $status === 'done',
            ]);

            if (in_array($status, ['processing', 'done'], true) && $vendorList->isNotEmpty()) {
                $vendor = $vendorList->get($index % max(1, $vendorList->count()));
                $expenseAmount = (int) round(((int) ($vendor->harga_vendor ?? 2000000)));

                Expense::withoutGlobalScopes()->firstOrCreate(
                    [
                        'company_id' => $company->id,
                        'order_id' => $order->id,
                        'vendor_id' => $vendor->id,
                    ],
                    [
                        'amount' => $expenseAmount,
                        'note' => 'Pembayaran vendor '.$vendor->name.' untuk '.$order->name,
                        'date_expense' => $createdAt->copy()->addDays(14)->toDateString(),
                        'payment_method_id' => $rekening->id,
                        'kategori_transaksi' => 'uang_keluar',
                    ]
                );
            }
        }

        $this->command->info("Proyek wedding: {$created} baru (target 10).");
    }

    private function ensurePayment(Order $order, Company $company, PaymentMethod $rekening, int $nominal, Carbon $date, string $keterangan): void
    {
        if ($nominal <= 0) {
            return;
        }

        DataPembayaran::withoutGlobalScopes()->firstOrCreate(
            [
                'company_id' => $company->id,
                'order_id' => $order->id,
                'keterangan' => $keterangan,
            ],
            [
                'nominal' => $nominal,
                'tgl_bayar' => $date->toDateString(),
                'payment_method_id' => $rekening->id,
                'kategori_transaksi' => 'uang_masuk',
            ]
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function vendorDefinitions(): array
    {
        return [
            ['name' => 'Pelaminan Emas Jakarta', 'category' => 'dekorasi-pelaminan', 'pic' => 'Sari Dewi', 'phone' => '8121111001', 'address' => 'Jl. Kemang Raya No. 12, Jakarta Selatan', 'description' => 'Dekorasi pelaminan mewah dengan bunga segar dan lighting romantic.', 'harga_publish' => 15000000, 'harga_vendor' => 12000000, 'bank' => 'BCA', 'account' => '1234567890', 'holder' => 'Sari Dewi Decoration'],
            ['name' => 'Backdrop Fresh Flower', 'category' => 'dekorasi-pelaminan', 'pic' => 'Lina Florist', 'phone' => '8121111002', 'address' => 'Jl. Melawai No. 45, Jakarta Selatan', 'description' => 'Backdrop bunga import dan lokal untuk akad dan resepsi.', 'harga_publish' => 8500000, 'harga_vendor' => 6800000, 'bank' => 'Mandiri', 'account' => '1234567891', 'holder' => 'Lina Fresh Flower'],
            ['name' => 'Lighting Pelaminan Pro', 'category' => 'dekorasi-pelaminan', 'pic' => 'Agus Lighting', 'phone' => '8121111003', 'address' => 'Jl. Casablanca No. 8, Jakarta', 'description' => 'Uplighting, spotlight couple, dan ambience LED.', 'harga_publish' => 6500000, 'harga_vendor' => 5200000, 'bank' => 'BRI', 'account' => '1234567892', 'holder' => 'Agus Lighting Design'],
            ['name' => 'Catering Nusantara 300 Pax', 'category' => 'catering-makanan', 'pic' => 'Ahmad Rizki', 'phone' => '8121111004', 'address' => 'Jl. Raya Bogor No. 88, Bogor', 'description' => 'Prasmanan Indonesia lengkap dengan live cooking.', 'harga_publish' => 28000000, 'harga_vendor' => 23000000, 'bank' => 'BCA', 'account' => '1234567893', 'holder' => 'Ahmad Rizki Catering'],
            ['name' => 'Catering International Buffet', 'category' => 'catering-makanan', 'pic' => 'Chef Miranda', 'phone' => '8121111005', 'address' => 'Jl. Senopati No. 21, Jakarta Selatan', 'description' => 'Menu western, asian, dan dessert table premium.', 'harga_publish' => 35000000, 'harga_vendor' => 29000000, 'bank' => 'Mandiri', 'account' => '1234567894', 'holder' => 'Miranda Catering'],
            ['name' => 'Dessert Corner Premium', 'category' => 'catering-makanan', 'pic' => 'Dinda Pastry', 'phone' => '8121111006', 'address' => 'Jl. Ampera No. 10, Jakarta Selatan', 'description' => 'Macaron, mini cake, chocolate fountain.', 'harga_publish' => 4800000, 'harga_vendor' => 3900000, 'bank' => 'BNI', 'account' => '1234567895', 'holder' => 'Dinda Dessert Table'],
            ['name' => 'Foto Cinematic Wedding', 'category' => 'foto-video', 'pic' => 'Dedi Photographer', 'phone' => '8121111007', 'address' => 'Jl. Sudirman No. 50, Jakarta Pusat', 'description' => 'Dokumentasi foto wedding cinematic plus album.', 'harga_publish' => 12000000, 'harga_vendor' => 9500000, 'bank' => 'BCA', 'account' => '1234567896', 'holder' => 'Dedi Cinematic Studio'],
            ['name' => 'Video Same Day Edit', 'category' => 'foto-video', 'pic' => 'Reza Video', 'phone' => '8121111008', 'address' => 'Jl. Serpong No. 7, Tangerang', 'description' => 'SDE, highlight reel, dan full ceremony coverage.', 'harga_publish' => 9500000, 'harga_vendor' => 7600000, 'bank' => 'BRI', 'account' => '1234567897', 'holder' => 'Reza Cinematic Video'],
            ['name' => 'Drone Aerial Wedding', 'category' => 'foto-video', 'pic' => 'Arif Drone', 'phone' => '8121111009', 'address' => 'Jl. Fatmawati No. 33, Jakarta Selatan', 'description' => 'Aerial 4K dengan pilot bersertifikat.', 'harga_publish' => 4500000, 'harga_vendor' => 3600000, 'bank' => 'Mandiri', 'account' => '1234567898', 'holder' => 'Arif Drone Services'],
            ['name' => 'Sound Line Array Pro', 'category' => 'sound-system-audio', 'pic' => 'Eko Saputra', 'phone' => '8121111010', 'address' => 'Jl. Gatot Subroto No. 90, Jakarta', 'description' => 'Line array, mixer digital, operator berpengalaman.', 'harga_publish' => 7500000, 'harga_vendor' => 6000000, 'bank' => 'BNI', 'account' => '1234567899', 'holder' => 'Eko Audio Visual'],
            ['name' => 'Wireless Mic & Monitor', 'category' => 'sound-system-audio', 'pic' => 'Rama Audio', 'phone' => '8121111011', 'address' => 'Jl. Tebet Raya No. 14, Jakarta Selatan', 'description' => 'Mic wireless premium dan in-ear monitor.', 'harga_publish' => 3200000, 'harga_vendor' => 2500000, 'bank' => 'BCA', 'account' => '1234567900', 'holder' => 'Rama Audio Rental'],
            ['name' => 'LED Screen Ceremony', 'category' => 'sound-system-audio', 'pic' => 'Budi LED', 'phone' => '8121111012', 'address' => 'Jl. Cawang No. 5, Jakarta Timur', 'description' => 'LED wall untuk akad, live feed, dan ucapan.', 'harga_publish' => 6800000, 'harga_vendor' => 5400000, 'bank' => 'Mandiri', 'account' => '1234567901', 'holder' => 'Budi LED Production'],
            ['name' => 'MUA Pengantin Professional', 'category' => 'make-up-beauty', 'pic' => 'Fitri Makeup', 'phone' => '8121111013', 'address' => 'Jl. Kemang Selatan No. 18, Jakarta', 'description' => 'Makeup akad & resepsi, trial, dan touch up.', 'harga_publish' => 3500000, 'harga_vendor' => 2800000, 'bank' => 'BCA', 'account' => '1234567902', 'holder' => 'Fitri Maharani'],
            ['name' => 'Hair Stylist Bridal', 'category' => 'make-up-beauty', 'pic' => 'Citra Hair', 'phone' => '8121111014', 'address' => 'Jl. Kelapa Gading No. 22, Jakarta Utara', 'description' => 'Hair styling pengantin plus aksesoris.', 'harga_publish' => 2200000, 'harga_vendor' => 1800000, 'bank' => 'BRI', 'account' => '1234567903', 'holder' => 'Citra Bridal Hair'],
            ['name' => 'Kebaya & Attire Styling', 'category' => 'make-up-beauty', 'pic' => 'Kartika Kebaya', 'phone' => '8121111015', 'address' => 'Jl. Pondok Indah No. 9, Jakarta Selatan', 'description' => 'Fitting kebaya, jas pengantin, dan accessorize.', 'harga_publish' => 5500000, 'harga_vendor' => 4400000, 'bank' => 'Mandiri', 'account' => '1234567904', 'holder' => 'Kartika Kebaya House'],
            ['name' => 'Wedding Car Mercy S-Class', 'category' => 'transportation', 'pic' => 'Indra Mobil', 'phone' => '8121111016', 'address' => 'Jl. HR Rasuna Said No. 11, Jakarta', 'description' => 'Mercy S-Class plus dekor bunga dan driver.', 'harga_publish' => 3500000, 'harga_vendor' => 2800000, 'bank' => 'BCA', 'account' => '1234567905', 'holder' => 'Indra Luxury Car'],
            ['name' => 'Shuttle Tamu Hiace', 'category' => 'transportation', 'pic' => 'Hendra Transport', 'phone' => '8121111017', 'address' => 'Jl. TB Simatupang No. 40, Jakarta', 'description' => 'Armada Hiace AC antar jemput tamu.', 'harga_publish' => 3800000, 'harga_vendor' => 3100000, 'bank' => 'BNI', 'account' => '1234567906', 'holder' => 'Hendra Transportation'],
            ['name' => 'MC Bilingual Wedding', 'category' => 'entertainment-mc', 'pic' => 'Andre MC', 'phone' => '8121111018', 'address' => 'Jl. Radio Dalam No. 3, Jakarta Selatan', 'description' => 'MC Indonesia-English untuk akad dan resepsi.', 'harga_publish' => 3800000, 'harga_vendor' => 3100000, 'bank' => 'Mandiri', 'account' => '1234567907', 'holder' => 'Andre Professional MC'],
            ['name' => 'Live Band Acoustic Duo', 'category' => 'entertainment-mc', 'pic' => 'Ricky Band', 'phone' => '8121111019', 'address' => 'Jl. Blok M No. 16, Jakarta Selatan', 'description' => 'Acoustic duo love songs selama resepsi.', 'harga_publish' => 6500000, 'harga_vendor' => 5200000, 'bank' => 'BCA', 'account' => '1234567908', 'holder' => 'Ricky Music Entertainment'],
            ['name' => 'Gamelan Upacara Adat', 'category' => 'entertainment-mc', 'pic' => 'Pak Slamet', 'phone' => '8121111020', 'address' => 'Jl. Kaliurang No. 2, Yogyakarta', 'description' => 'Gamelan lengkap untuk upacara adat Jawa.', 'harga_publish' => 4000000, 'harga_vendor' => 3200000, 'bank' => 'BRI', 'account' => '1234567909', 'holder' => 'Slamet Gamelan Group'],
            ['name' => 'Full Wedding Coordination', 'category' => 'wedding-organizer', 'pic' => 'Ratna WO', 'phone' => '8121111021', 'address' => 'Jl. Kuningan No. 7, Jakarta Selatan', 'description' => 'Full planning, rundown, dan vendor coordination.', 'harga_publish' => 12000000, 'harga_vendor' => 9500000, 'bank' => 'BCA', 'account' => '1234567910', 'holder' => 'Ratna Wedding Consultant'],
            ['name' => 'Day Of Coordinator', 'category' => 'wedding-organizer', 'pic' => 'Tia Coordinator', 'phone' => '8121111022', 'address' => 'Jl. Gatot Subroto No. 19, Jakarta', 'description' => 'Koordinasi hari-H, timeline, dan guest flow.', 'harga_publish' => 4500000, 'harga_vendor' => 3600000, 'bank' => 'Mandiri', 'account' => '1234567911', 'holder' => 'Tia Wedding Coordinator'],
            ['name' => 'Ballroom Senayan Grand', 'category' => 'venue-gedung', 'pic' => 'Dewi Sartika', 'phone' => '8121111023', 'address' => 'Jl. Jenderal Sudirman, Jakarta Pusat', 'description' => 'Ballroom 400-800 pax dengan stage dan bridal room.', 'harga_publish' => 45000000, 'harga_vendor' => 38000000, 'bank' => 'BCA', 'account' => '1234567912', 'holder' => 'Grand Ballroom Senayan'],
            ['name' => 'Garden Venue Puncak', 'category' => 'venue-gedung', 'pic' => 'Bambang Garden', 'phone' => '8121111024', 'address' => 'Jl. Puncak Pass No. 21, Bogor', 'description' => 'Garden outdoor plus indoor backup, 200-400 pax.', 'harga_publish' => 22000000, 'harga_vendor' => 18000000, 'bank' => 'BNI', 'account' => '1234567913', 'holder' => 'Bambang Garden Venue'],
            ['name' => 'Undangan Letterpress', 'category' => 'undangan-souvenir', 'pic' => 'Rina Design', 'phone' => '8121111025', 'address' => 'Jl. Cipete No. 8, Jakarta Selatan', 'description' => 'Undangan letterpress, RSVP, dan digital invite.', 'harga_publish' => 2800000, 'harga_vendor' => 2200000, 'bank' => 'Mandiri', 'account' => '1234567914', 'holder' => 'Rina Creative Design'],
            ['name' => 'Souvenir Custom Couple', 'category' => 'undangan-souvenir', 'pic' => 'Sari Souvenir', 'phone' => '8121111026', 'address' => 'Jl. Mangga Besar No. 30, Jakarta Barat', 'description' => 'Door gift custom dengan packaging personal.', 'harga_publish' => 3200000, 'harga_vendor' => 2600000, 'bank' => 'BCA', 'account' => '1234567915', 'holder' => 'Sari Wedding Favor'],
            ['name' => 'Photo Booth Polaroid', 'category' => 'lain-lain', 'pic' => 'Dika Booth', 'phone' => '8121111027', 'address' => 'Jl. Tebet Barat No. 4, Jakarta Selatan', 'description' => 'Photo booth instant print dan digital gallery.', 'harga_publish' => 2800000, 'harga_vendor' => 2300000, 'bank' => 'BRI', 'account' => '1234567916', 'holder' => 'Dika Creative Booth'],
            ['name' => 'Security & Parking Crew', 'category' => 'lain-lain', 'pic' => 'Joko Security', 'phone' => '8121111028', 'address' => 'Jl. Pancoran No. 6, Jakarta Selatan', 'description' => 'Petugas keamanan, valet, dan crowd control.', 'harga_publish' => 2800000, 'harga_vendor' => 2300000, 'bank' => 'Mandiri', 'account' => '1234567917', 'holder' => 'Joko Security Service'],
            ['name' => 'Ice Cream Cart Live', 'category' => 'catering-makanan', 'pic' => 'Nia Gelato', 'phone' => '8121111029', 'address' => 'Jl. Kemang Utara No. 2, Jakarta Selatan', 'description' => 'Live gelato cart selama resepsi.', 'harga_publish' => 3500000, 'harga_vendor' => 2800000, 'bank' => 'BCA', 'account' => '1234567918', 'holder' => 'Nia Gelato Cart'],
            ['name' => 'Florist Bridal Bouquet', 'category' => 'dekorasi-pelaminan', 'pic' => 'Sinta Bunga', 'phone' => '8121111030', 'address' => 'Jl. Melawai Raya No. 11, Jakarta Selatan', 'description' => 'Bouquet pengantin, corsage, dan petal toss.', 'harga_publish' => 4200000, 'harga_vendor' => 3400000, 'bank' => 'BNI', 'account' => '1234567919', 'holder' => 'Sinta Flower Shop'],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function productDefinitions(): array
    {
        return [
            [
                'name' => 'Paket Intimate 150 Pax',
                'category' => 'wedding-organizer',
                'pax' => 150,
                'description' => 'Paket intimate untuk 150 tamu: MUA, mobil, MC, dan koordinasi hari-H.',
                'pengurangan' => 1500000,
                'vendors' => [
                    'ruwo-mua-pengantin-professional',
                    'ruwo-wedding-car-mercy-s-class',
                    'ruwo-mc-bilingual-wedding',
                    'ruwo-day-of-coordinator',
                ],
            ],
            [
                'name' => 'Paket Garden 200 Pax',
                'category' => 'venue-gedung',
                'pax' => 200,
                'description' => 'Garden party 200 pax di Puncak dengan dekor, catering, dan sound.',
                'pengurangan' => 2000000,
                'vendors' => [
                    'ruwo-garden-venue-puncak',
                    'ruwo-pelaminan-emas-jakarta',
                    'ruwo-catering-nusantara-300-pax',
                    'ruwo-sound-line-array-pro',
                ],
            ],
            [
                'name' => 'Paket Ballroom 300 Pax',
                'category' => 'venue-gedung',
                'pax' => 300,
                'description' => 'Ballroom Senayan 300 pax dengan lighting, LED, dan dokumentasi.',
                'pengurangan' => 2500000,
                'vendors' => [
                    'ruwo-ballroom-senayan-grand',
                    'ruwo-lighting-pelaminan-pro',
                    'ruwo-led-screen-ceremony',
                    'ruwo-foto-cinematic-wedding',
                ],
            ],
            [
                'name' => 'Paket Adat Jawa 400 Pax',
                'category' => 'entertainment-mc',
                'pax' => 400,
                'description' => 'Upacara adat Jawa lengkap: gamelan, kebaya, pelaminan, dan catering.',
                'pengurangan' => 0,
                'vendors' => [
                    'ruwo-gamelan-upacara-adat',
                    'ruwo-kebaya-attire-styling',
                    'ruwo-pelaminan-emas-jakarta',
                    'ruwo-catering-nusantara-300-pax',
                ],
            ],
            [
                'name' => 'Paket Modern Minimalist 250 Pax',
                'category' => 'foto-video',
                'pax' => 250,
                'description' => 'Konsep modern: SDE, drone, LED, dan live band.',
                'pengurangan' => 3000000,
                'vendors' => [
                    'ruwo-video-same-day-edit',
                    'ruwo-drone-aerial-wedding',
                    'ruwo-led-screen-ceremony',
                    'ruwo-live-band-acoustic-duo',
                ],
            ],
            [
                'name' => 'Paket Beach Sunset 180 Pax',
                'category' => 'foto-video',
                'pax' => 180,
                'description' => 'Dokumentasi sunset, florist, dessert, dan photo booth.',
                'pengurangan' => 1800000,
                'vendors' => [
                    'ruwo-foto-cinematic-wedding',
                    'ruwo-florist-bridal-bouquet',
                    'ruwo-dessert-corner-premium',
                    'ruwo-photo-booth-polaroid',
                ],
            ],
            [
                'name' => 'Paket Premium 500 Pax',
                'category' => 'venue-gedung',
                'pax' => 500,
                'description' => 'Paket besar: ballroom, catering internasional, full WO, dan security.',
                'pengurangan' => 5000000,
                'vendors' => [
                    'ruwo-ballroom-senayan-grand',
                    'ruwo-catering-international-buffet',
                    'ruwo-full-wedding-coordination',
                    'ruwo-security-parking-crew',
                ],
            ],
            [
                'name' => 'Paket Rustic Vintage 220 Pax',
                'category' => 'dekorasi-pelaminan',
                'pax' => 220,
                'description' => 'Tema rustic: backdrop bunga, undangan letterpress, souvenir, dan ice cream cart.',
                'pengurangan' => 2200000,
                'vendors' => [
                    'ruwo-backdrop-fresh-flower',
                    'ruwo-undangan-letterpress',
                    'ruwo-souvenir-custom-couple',
                    'ruwo-ice-cream-cart-live',
                ],
            ],
            [
                'name' => 'Paket Islamic 350 Pax',
                'category' => 'wedding-organizer',
                'pax' => 350,
                'description' => 'Akad & resepsi Islami: WO, MUA, catering, dan shuttle tamu.',
                'pengurangan' => 0,
                'vendors' => [
                    'ruwo-full-wedding-coordination',
                    'ruwo-mua-pengantin-professional',
                    'ruwo-catering-nusantara-300-pax',
                    'ruwo-shuttle-tamu-hiace',
                ],
            ],
            [
                'name' => 'Paket Destination Puncak 160 Pax',
                'category' => 'venue-gedung',
                'pax' => 160,
                'description' => 'Destination di Puncak: venue garden, drone, hair styling, dan day-of coordinator.',
                'pengurangan' => 2500000,
                'vendors' => [
                    'ruwo-garden-venue-puncak',
                    'ruwo-drone-aerial-wedding',
                    'ruwo-hair-stylist-bridal',
                    'ruwo-day-of-coordinator',
                ],
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function prospectDefinitions(): array
    {
        return [
            ['name_event' => 'Wedding Andi & Sari', 'cpp' => 'Andi Pratama', 'cpw' => 'Sari Dewi', 'venue' => 'Rumah Keluarga, Jakarta Selatan', 'phone' => '8122001001', 'address' => 'Jl. Kemang Raya No. 10, Jakarta Selatan', 'penawaran' => 45000000, 'notes' => 'Intimate 150 pax, tema ivory blush.'],
            ['name_event' => 'Wedding Budi & Maya', 'cpp' => 'Budi Santoso', 'cpw' => 'Maya Putri', 'venue' => 'Garden Venue Puncak', 'phone' => '8122001002', 'address' => 'Jl. Puncak Pass, Bogor', 'penawaran' => 72000000, 'notes' => 'Garden party outdoor, backup indoor jika hujan.'],
            ['name_event' => 'Wedding Dedi & Rina', 'cpp' => 'Dedi Kurniawan', 'cpw' => 'Rina Sari', 'venue' => 'Ballroom Senayan Grand', 'phone' => '8122001003', 'address' => 'Jl. Jenderal Sudirman, Jakarta', 'penawaran' => 125000000, 'notes' => 'Resepsi ballroom 300 pax, lighting premium.'],
            ['name_event' => 'Wedding Eko & Fitri', 'cpp' => 'Eko Prasetyo', 'cpw' => 'Fitri Handayani', 'venue' => 'Pendopo Adat, Yogyakarta', 'phone' => '8122001004', 'address' => 'Jl. Kaliurang, Yogyakarta', 'penawaran' => 88000000, 'notes' => 'Adat Jawa lengkap dengan gamelan.'],
            ['name_event' => 'Wedding Fajar & Indira', 'cpp' => 'Fajar Nugroho', 'cpw' => 'Indira Salsabila', 'venue' => 'Art Space Senopati', 'phone' => '8122001005', 'address' => 'Jl. Senopati No. 8, Jakarta Selatan', 'penawaran' => 95000000, 'notes' => 'Modern minimalist, same day edit wajib.'],
            ['name_event' => 'Wedding Galih & Hana', 'cpp' => 'Galih Ramadhan', 'cpw' => 'Hana Pertiwi', 'venue' => 'Pantai Anyer Resort', 'phone' => '8122001006', 'address' => 'Jl. Raya Anyer, Banten', 'penawaran' => 68000000, 'notes' => 'Sunset beach, dokumentasi drone.'],
            ['name_event' => 'Wedding Ivan & Julia', 'cpp' => 'Ivan Kurniawan', 'cpw' => 'Julia Maharani', 'venue' => 'Ballroom Senayan Grand', 'phone' => '8122001007', 'address' => 'Jl. Melawai Raya, Jakarta Selatan', 'penawaran' => 185000000, 'notes' => 'Premium 500 pax, full WO.'],
            ['name_event' => 'Wedding Khalil & Lina', 'cpp' => 'Khalil Ahmad', 'cpw' => 'Lina Safitri', 'venue' => 'Barn Venue, Bogor', 'phone' => '8122001008', 'address' => 'Jl. Raya Bogor No. 21, Bogor', 'penawaran' => 79000000, 'notes' => 'Rustic vintage, undangan letterpress.'],
            ['name_event' => 'Wedding Nurdin & Olivia', 'cpp' => 'Nurdin Hasibuan', 'cpw' => 'Olivia Tambunan', 'venue' => 'Masjid & JCC Senayan', 'phone' => '8122001009', 'address' => 'Jl. Tebet Raya, Jakarta Selatan', 'penawaran' => 82000000, 'notes' => 'Akad masjid, resepsi JCC, catering halal.'],
            ['name_event' => 'Wedding Putra & Queen', 'cpp' => 'Putra Mahendra', 'cpw' => 'Queen Elisabeth', 'venue' => 'Villa Puncak Resort', 'phone' => '8122001010', 'address' => 'Jl. Puncak Raya, Bogor', 'penawaran' => 98000000, 'notes' => 'Destination 2H1M di Puncak.'],
        ];
    }
}
