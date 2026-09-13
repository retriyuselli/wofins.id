<?php

namespace Database\Seeders;

use App\Models\BankStatement;
use App\Models\Category;
use App\Models\Company;
use App\Models\DataPembayaran;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\ExpenseOps;
use App\Models\FixedAsset;
use App\Models\NotaDinas;
use App\Models\NotaDinasDetail;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\PaymentMethod;
use App\Models\Payroll;
use App\Models\Product;
use App\Models\ProductPengurangan;
use App\Models\ProductVendor;
use App\Models\Prospect;
use App\Models\SimulasiProduk;
use App\Models\Status;
use App\Models\User;
use App\Models\Vendor;
use App\Support\CompanySubscription;
use App\Support\DefaultCategories;
use App\Support\PackageRolePermissions;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Tenant demo: Retri Yuselli — paket Professional.
 *
 * php artisan db:seed --class=RetriYuselliProfessionalDemoSeeder
 */
class RetriYuselliProfessionalDemoSeeder extends Seeder
{
    private const OWNER_EMAIL = 'retriyuselli@gmail.com';

    private const OWNER_NAME = 'Retri Yuselli';

    private const PLAN = 'professional';

    private const SLUG_PREFIX = 'rywo-';

    public function run(): void
    {
        $createdUser = false;

        $user = User::query()->where('email', self::OWNER_EMAIL)->first();

        if (! $user) {
            $status = Status::query()->where('status_name', 'Account Manager')->first()
                ?? Status::query()->first();

            $user = User::query()->create([
                'name' => self::OWNER_NAME,
                'email' => self::OWNER_EMAIL,
                'password' => 'WofinsPro2026',
                'phone_number' => '81373180001',
                'address' => 'Jl. Jenderal Sudirman No. 18, Palembang',
                'gender' => 'female',
                'department' => 'bisnis',
                'status' => 'active',
                'status_id' => $status?->id,
                'email_verified_at' => now(),
                'hire_date' => now()->subYears(3)->toDateString(),
            ]);
            $createdUser = true;
        }

        $role = Role::findOrCreate('pengunjung', 'web');
        if (! $user->hasRole($role)) {
            $user->assignRole($role);
        }

        $company = $user->company_id
            ? Company::query()->find($user->company_id)
            : Company::query()->where('email', self::OWNER_EMAIL)->first();

        if (! $company) {
            $company = Company::query()->create([
                'company_name' => 'Retri Yuselli WO',
                'business_license' => 'NIB-RYWO-'.$user->id,
                'owner_name' => self::OWNER_NAME,
                'jabatan_owner' => 'Owner',
                'inisial_wo' => 'RY',
                'email' => self::OWNER_EMAIL,
                'phone' => '81373180001',
                'address' => 'Jl. Jenderal Sudirman No. 18, Palembang',
                'city' => 'Palembang',
                'province' => 'Sumatera Selatan',
                'postal_code' => '30126',
                'description' => 'Wedding organizer Palembang — paket Professional WOFINS.',
                'established_year' => 2019,
                'employee_count' => 1,
                'subscription_plan' => self::PLAN,
                'subscription_expires_at' => now()->addYear(),
                'is_active' => true,
            ]);
        } else {
            $company->forceFill([
                'subscription_plan' => self::PLAN,
                'subscription_expires_at' => $company->subscription_expires_at ?: now()->addYear(),
                'is_active' => true,
                'inisial_wo' => $company->inisial_wo ?: 'RY',
            ])->save();
        }

        if ((int) $user->company_id !== (int) $company->id) {
            $user->forceFill([
                'company_id' => $company->id,
                'created_by' => null,
            ])->save();
        }

        CompanySubscription::forgetCache($company->id);
        PackageRolePermissions::syncPengunjungRole();
        DefaultCategories::ensureForCompany($company);

        $categories = Category::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->get()
            ->keyBy('slug');

        if ($categories->isEmpty()) {
            $this->command->error('Kategori company kosong.');

            return;
        }

        DB::transaction(function () use ($user, $company, $categories) {
            $vendors = $this->seedVendors($user, $company, $categories);
            $products = $this->seedProducts($user, $company, $categories, $vendors);
            $rekening = $this->seedRekening($company);
            $prospects = $this->seedProspects($user, $company);
            $this->seedSimulasi($user, $company, $prospects, $products);
            $this->seedOrders($user, $company, $prospects, $products, $vendors, $rekening);
            $this->seedNotaDinas($user, $company, $vendors, $prospects);
            $employees = $this->seedEmployees($user, $company);
            $this->seedPayrolls($company, $employees);
            $this->seedFixedAssets($company);
            $this->seedBankStatement($company, $rekening);
            $this->seedExpenseOps($company, $rekening);
        });

        $cid = $company->id;
        $this->command->info("Seeding Professional untuk {$user->name} / {$company->company_name} (company_id={$cid})");
        if ($createdUser) {
            $this->command->warn('User baru. Password awal: WofinsPro2026 (ganti setelah login).');
        }
        $this->command->table(
            ['Data', 'Jumlah'],
            [
                ['Paket', $company->fresh()->subscription_plan],
                ['Vendor', Vendor::withoutGlobalScopes()->where('company_id', $cid)->count()],
                ['Produk', Product::withoutGlobalScopes()->where('company_id', $cid)->count()],
                ['Rekening', PaymentMethod::withoutGlobalScopes()->where('company_id', $cid)->count()],
                ['Prospek', Prospect::withoutGlobalScopes()->where('company_id', $cid)->count()],
                ['Simulasi', SimulasiProduk::withoutGlobalScopes()->where('company_id', $cid)->count()],
                ['Proyek', Order::withoutGlobalScopes()->where('company_id', $cid)->count()],
                ['Nota Dinas', NotaDinas::withoutGlobalScopes()->where('company_id', $cid)->count()],
                ['Karyawan', Employee::withoutGlobalScopes()->where('company_id', $cid)->count()],
                ['Payroll', Payroll::withoutGlobalScopes()->where('company_id', $cid)->count()],
                ['Aset tetap', FixedAsset::withoutGlobalScopes()->where('company_id', $cid)->count()],
                ['Rekening koran', BankStatement::withoutGlobalScopes()->where('company_id', $cid)->count()],
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
            $category = $categories->get($row['category']) ?? $categories->get('lain-lain') ?? $categories->first();
            $slug = self::SLUG_PREFIX.Str::slug($row['name']);

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
                    'harga_publish' => $row['harga_publish'],
                    'harga_vendor' => $row['harga_vendor'],
                    'bank_name' => $row['bank'],
                    'bank_account' => $row['account'],
                    'account_holder' => $row['holder'],
                ]
            );

            if ($vendor->wasRecentlyCreated) {
                $created++;
            }
        }

        $this->command->info("Vendor: {$created} baru.");

        return Vendor::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('slug', 'like', self::SLUG_PREFIX.'%')
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
            $slug = self::SLUG_PREFIX.Str::slug($row['name']);

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

                $pricePublic = (int) $vendor->harga_publish;
                $priceVendor = (int) $vendor->harga_vendor;
                $totalPublish += $pricePublic;

                ProductVendor::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'vendor_id' => $vendor->id,
                    ],
                    [
                        'harga_publish' => $vendor->harga_publish,
                        'harga_vendor' => $vendor->harga_vendor,
                        'quantity' => 1,
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
                    ['amount' => $pengurangan, 'notes' => '<p>Diskon seeder Professional Retri Yuselli WO.</p>']
                );
            }

            $product->update([
                'product_price' => $totalPublish,
                'pengurangan' => $pengurangan,
                'price' => max(0, $totalPublish - $pengurangan),
            ]);

            $products->push($product->fresh());
        }

        $this->command->info("Produk: {$created} baru.");

        return $products->values();
    }

    private function seedRekening(Company $company): PaymentMethod
    {
        $existing = PaymentMethod::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        return PaymentMethod::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'name' => 'Retri Yuselli',
            'bank_name' => 'Bank Sumsel Babel',
            'cabang' => 'Palembang Sudirman',
            'no_rekening' => '9050213298',
            'is_cash' => false,
            'opening_balance' => 15000000,
            'opening_balance_date' => now()->startOfYear()->toDateString(),
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, Prospect>
     */
    private function seedProspects(User $user, Company $company)
    {
        $created = 0;
        $prospects = collect();

        foreach ($this->prospectDefinitions() as $index => $row) {
            $base = Carbon::now()->addMonths($index + 1)->startOfMonth()->addDays(8 + $index);

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

        $this->command->info("Prospek: {$created} baru.");

        return $prospects->values();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Prospect>  $prospects
     * @param  \Illuminate\Support\Collection<int, Product>  $products
     */
    private function seedSimulasi(User $user, Company $company, $prospects, $products): void
    {
        $created = 0;

        foreach ($prospects->take(5) as $index => $prospect) {
            $product = $products->get($index) ?? $products->first();
            if (! $product) {
                break;
            }

            $price = (int) $product->price;
            $penambahan = [1500000, 0, 2500000, 800000, 1200000][$index] ?? 0;
            $pengurangan = [500000, 0, 1000000, 0, 400000][$index] ?? 0;
            $grand = max(0, $price + $penambahan - $pengurangan);
            $slug = self::SLUG_PREFIX.Str::slug($prospect->name_event).'-sim';

            $dp = (int) round($grand * 0.30);
            $term2 = (int) round($grand * 0.40);
            $term3 = $grand - $dp - $term2;

            $simulasi = SimulasiProduk::withoutGlobalScopes()->firstOrCreate(
                [
                    'company_id' => $company->id,
                    'slug' => $slug,
                ],
                [
                    'prospect_id' => $prospect->id,
                    'product_id' => $product->id,
                    'user_id' => $user->id,
                    'total_price' => $price,
                    'penambahan' => $penambahan,
                    'pengurangan' => $pengurangan,
                    'promo' => 0,
                    'grand_total' => $grand,
                    'total_simulation' => $grand,
                    'payment_dp_amount' => $dp,
                    'payment_simulation' => [
                        ['label' => 'DP 30%', 'amount' => $dp],
                        ['label' => 'Termin 2 40%', 'amount' => $term2],
                        ['label' => 'Pelunasan 30%', 'amount' => $term3],
                    ],
                    'notes' => '<p>Simulasi Professional untuk '.$prospect->name_event.' — paket '.$product->name.'. Klien: '.trim($prospect->name_cpp.' & '.$prospect->name_cpw).'.</p>',
                    'contract_number' => 'SIM/RY/'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT).'/'.now()->year,
                    'name_ttd' => self::OWNER_NAME,
                    'title_ttd' => 'Owner',
                ]
            );

            if ($simulasi->wasRecentlyCreated) {
                $created++;
            }
        }

        $this->command->info("Simulasi wedding: {$created} baru.");
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
        $statuses = ['processing', 'pending', 'done', 'processing', 'pending'];
        $prefix = $company->woInitial();
        $created = 0;
        $vendorList = $vendors->values();

        foreach ($prospects->take(5) as $index => $prospect) {
            $product = $products->get($index) ?? $products->first();
            if (! $product) {
                break;
            }

            $number = sprintf('%s-%06d', $prefix, 300001 + $index);
            $status = $statuses[$index] ?? 'pending';
            $createdAt = Carbon::now()->subMonths(5 - $index)->addDays($index * 3)->setTime(10, 0);

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
                    'note' => '<p>Proyek wedding '.$prospect->name_event.' — Retri Yuselli WO.</p>',
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

            $dp = (int) round(((int) $product->price) * 0.30);
            $this->ensurePayment($order, $company, $rekening, $dp, $createdAt->copy()->addDays(7), 'DP '.$order->name);

            if ($status === 'done') {
                $pelunasan = ((int) $product->price) - $dp;
                $this->ensurePayment($order, $company, $rekening, $pelunasan, $createdAt->copy()->addDays(40), 'Pelunasan '.$order->name);
            }

            $paid = DataPembayaran::withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->where('order_id', $order->id)
                ->sum('nominal');

            $order->update([
                'total_price' => (int) $product->price,
                'grand_total' => (int) $product->price,
                'paid_amount' => $paid,
                'closing_date' => $createdAt->copy()->addDays(7)->toDateString(),
                'is_paid' => $status === 'done',
            ]);

            if ($vendorList->isNotEmpty()) {
                $vendor = $vendorList->get($index % max(1, $vendorList->count()));
                Expense::withoutGlobalScopes()->firstOrCreate(
                    [
                        'company_id' => $company->id,
                        'order_id' => $order->id,
                        'vendor_id' => $vendor->id,
                    ],
                    [
                        'amount' => (int) ($vendor->harga_vendor ?? 2000000),
                        'note' => 'Pembayaran vendor '.$vendor->name.' untuk '.$order->name,
                        'date_expense' => $createdAt->copy()->addDays(14)->toDateString(),
                        'payment_method_id' => $rekening->id,
                        'kategori_transaksi' => 'uang_keluar',
                    ]
                );
            }
        }

        $this->command->info("Proyek wedding: {$created} baru.");
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
     * @param  \Illuminate\Support\Collection<string, Vendor>  $vendors
     * @param  \Illuminate\Support\Collection<int, Prospect>  $prospects
     */
    private function seedNotaDinas(User $user, Company $company, $vendors, $prospects): void
    {
        $rows = [
            ['kategori' => 'BIS', 'hal' => 'Pembayaran DP vendor dekorasi wedding Palembang', 'status' => 'diajukan'],
            ['kategori' => 'OPS', 'hal' => 'Biaya operasional survey venue Jakabaring', 'status' => 'draft'],
        ];

        foreach ($rows as $index => $row) {
            $noNd = NotaDinas::generateNomorND($row['kategori'], now()->year, (int) $company->id);

            $nd = NotaDinas::withoutGlobalScopes()->firstOrCreate(
                [
                    'company_id' => $company->id,
                    'hal' => $row['hal'],
                ],
                [
                    'no_nd' => $noNd,
                    'kategori_nd' => $row['kategori'],
                    'tanggal' => now()->subDays(3 - $index)->toDateString(),
                    'pengirim_id' => $user->id,
                    'penerima_id' => $user->id,
                    'sifat' => 'biasa',
                    'status' => $row['status'],
                    'catatan' => 'Seeder Professional Retri Yuselli WO.',
                ]
            );

            $vendor = $vendors->values()->get($index);
            if ($vendor && $nd->details()->doesntExist()) {
                NotaDinasDetail::withoutGlobalScopes()->create([
                    'company_id' => $company->id,
                    'nota_dinas_id' => $nd->id,
                    'vendor_id' => $vendor->id,
                    'keperluan' => $row['hal'],
                    'event' => $prospects->get($index)?->name_event,
                    'jumlah_transfer' => (int) round(((int) $vendor->harga_vendor) * 0.3),
                    'bank_name' => $vendor->bank_name,
                    'bank_account' => $vendor->bank_account,
                    'account_holder' => $vendor->account_holder,
                    'status_invoice' => 'belum_dibayar',
                    'jenis_pengeluaran' => $row['kategori'] === 'OPS' ? 'operasional' : 'lain-lain',
                ]);
            }
        }
    }

    /**
     * @return \Illuminate\Support\Collection<int, Employee>
     */
    private function seedEmployees(User $user, Company $company)
    {
        $rows = [
            [
                'name' => self::OWNER_NAME,
                'email' => self::OWNER_EMAIL,
                'phone' => '81373180001',
                'position' => 'Owner / Account Manager',
                'salary' => 12000000,
                'tunjangan' => 1500000,
                'user_id' => $user->id,
            ],
            [
                'name' => 'Siti Aisyah Putri',
                'email' => 'aisyah.rywo@example.com',
                'phone' => '81373180002',
                'position' => 'Event Manager',
                'salary' => 6500000,
                'tunjangan' => 750000,
                'user_id' => null,
            ],
        ];

        $employees = collect();

        foreach ($rows as $row) {
            $employee = Employee::withoutGlobalScopes()->firstOrCreate(
                [
                    'company_id' => $company->id,
                    'email' => $row['email'],
                ],
                [
                    'name' => $row['name'],
                    'phone' => $row['phone'],
                    'address' => 'Palembang, Sumatera Selatan',
                    'position' => $row['position'],
                    'salary' => $row['salary'],
                    'tunjangan' => $row['tunjangan'],
                    'date_of_join' => now()->subYears(2)->toDateString(),
                    'user_id' => $row['user_id'],
                    'bank_name' => 'Bank Sumsel Babel',
                    'no_rek' => '9050'.substr(preg_replace('/\D/', '', $row['phone']), -6),
                    'note' => 'Seeder Professional Retri Yuselli WO.',
                ]
            );
            $employees->push($employee);
        }

        return $employees->values();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Employee>  $employees
     */
    private function seedPayrolls(Company $company, $employees): void
    {
        foreach ($employees as $employee) {
            Payroll::withoutGlobalScopes()->firstOrCreate(
                [
                    'company_id' => $company->id,
                    'employee_id' => $employee->id,
                    'period_month' => now()->month,
                    'period_year' => now()->year,
                ],
                [
                    'user_id' => $employee->user_id,
                    'gaji_pokok' => (int) $employee->salary,
                    'tunjangan' => (int) ($employee->tunjangan ?? 0),
                    'pengurangan' => 0,
                    'bonus' => 0,
                    'notes' => 'Payroll bulan berjalan — Retri Yuselli WO.',
                ]
            );
        }
    }

    private function seedFixedAssets(Company $company): void
    {
        $assets = [
            ['code' => 'RY-EQP-0001', 'name' => 'Kamera Sony A7 IV', 'category' => 'EQUIPMENT', 'price' => 42000000, 'location' => 'Studio Palembang'],
            ['code' => 'RY-CMP-0001', 'name' => 'Laptop MacBook Air M3', 'category' => 'COMPUTER', 'price' => 18500000, 'location' => 'Kantor Palembang'],
            ['code' => 'RY-VHC-0001', 'name' => 'Mobil Operasional Avanza', 'category' => 'VEHICLE', 'price' => 210000000, 'location' => 'Pool Palembang'],
            ['code' => 'RY-FRN-0001', 'name' => 'Set Meja Meeting WO', 'category' => 'FURNITURE', 'price' => 8500000, 'location' => 'Kantor Palembang'],
            ['code' => 'RY-EQP-0002', 'name' => 'Sound Portable 2 Speaker', 'category' => 'EQUIPMENT', 'price' => 12000000, 'location' => 'Gudang event'],
        ];

        foreach ($assets as $row) {
            FixedAsset::withoutGlobalScopes()->firstOrCreate(
                [
                    'company_id' => $company->id,
                    'asset_code' => $row['code'],
                ],
                [
                    'asset_name' => $row['name'],
                    'category' => $row['category'],
                    'purchase_date' => now()->subMonths(8)->toDateString(),
                    'purchase_price' => $row['price'],
                    'salvage_value' => (int) round($row['price'] * 0.15),
                    'useful_life_years' => 5,
                    'useful_life_months' => 0,
                    'accumulated_depreciation' => 0,
                    'current_book_value' => $row['price'],
                    'location' => $row['location'],
                    'condition' => 'GOOD',
                    'depreciation_method' => 'STRAIGHT_LINE',
                    'is_active' => true,
                    'notes' => 'Aset seeder Professional Retri Yuselli WO.',
                ]
            );
        }
    }

    private function seedBankStatement(Company $company, PaymentMethod $rekening): void
    {
        BankStatement::withoutGlobalScopes()->firstOrCreate(
            [
                'company_id' => $company->id,
                'title' => 'Rekening koran September 2026',
            ],
            [
                'payment_method_id' => $rekening->id,
                'period_start' => now()->startOfMonth()->toDateString(),
                'period_end' => now()->endOfMonth()->toDateString(),
                'source_type' => 'manual_input',
                'status' => 'pending',
                'file_path' => 'bank-statements/rywo-manual-sep-2026.txt',
                'original_filename' => 'rekening-koran-sep-2026.txt',
                'opening_balance' => (int) $rekening->opening_balance,
                'closing_balance' => (int) $rekening->opening_balance + 8500000,
                'no_of_debit' => 2,
                'tot_debit' => 600000,
                'no_of_credit' => 3,
                'tot_credit' => 9100000,
                'description' => 'Draft rekonsiliasi seeder Professional.',
                'uploaded_at' => now(),
            ]
        );
    }

    private function seedExpenseOps(Company $company, PaymentMethod $rekening): void
    {
        $rows = [
            ['name' => 'Bensin survey venue', 'amount' => 350000],
            ['name' => 'Paket data kantor', 'amount' => 250000],
        ];

        foreach ($rows as $row) {
            ExpenseOps::withoutGlobalScopes()->firstOrCreate(
                [
                    'company_id' => $company->id,
                    'name' => $row['name'],
                ],
                [
                    'amount' => $row['amount'],
                    'payment_method_id' => $rekening->id,
                    'date_expense' => now()->subDays(5)->toDateString(),
                    'note' => 'Pengeluaran operasional seeder Professional.',
                    'kategori_transaksi' => 'uang_keluar',
                ]
            );
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function vendorDefinitions(): array
    {
        return [
            ['name' => 'Pelaminan Songket Palembang', 'category' => 'dekorasi-pelaminan', 'pic' => 'Rina Dekor', 'phone' => '81273181001', 'address' => 'Jl. Merdeka No. 12, Palembang', 'description' => 'Pelaminan adat Palembang dengan songket dan bunga segar.', 'harga_publish' => 14000000, 'harga_vendor' => 11000000, 'bank' => 'Bank Sumsel Babel', 'account' => '9051111001', 'holder' => 'Rina Dekor Palembang'],
            ['name' => 'Florist Musi Fresh', 'category' => 'dekorasi-pelaminan', 'pic' => 'Sari Bunga', 'phone' => '81273181002', 'address' => 'Jl. Jend. Sudirman, Palembang', 'description' => 'Backdrop bunga dan bouquet pengantin.', 'harga_publish' => 7200000, 'harga_vendor' => 5800000, 'bank' => 'BCA', 'account' => '9051111002', 'holder' => 'Sari Florist'],
            ['name' => 'Catering Pempek & Prasmanan', 'category' => 'catering-makanan', 'pic' => 'Chef Andi', 'phone' => '81273181003', 'address' => 'Jl. Demang Lebar Daun, Palembang', 'description' => 'Prasmanan 200-400 pax plus live pempek.', 'harga_publish' => 26000000, 'harga_vendor' => 21000000, 'bank' => 'Mandiri', 'account' => '9051111003', 'holder' => 'Andi Catering'],
            ['name' => 'Foto Cinematic Palembang', 'category' => 'foto-video', 'pic' => 'Dedi Foto', 'phone' => '81273181004', 'address' => 'Jl. POM IX, Palembang', 'description' => 'Foto cinematic, album, dan same day edit.', 'harga_publish' => 11000000, 'harga_vendor' => 8800000, 'bank' => 'BCA', 'account' => '9051111004', 'holder' => 'Dedi Studio'],
            ['name' => 'Drone Sungai Musi', 'category' => 'foto-video', 'pic' => 'Arif Drone', 'phone' => '81273181005', 'address' => 'Jakabaring, Palembang', 'description' => 'Aerial 4K Ampera dan venue outdoor.', 'harga_publish' => 4200000, 'harga_vendor' => 3300000, 'bank' => 'BRI', 'account' => '9051111005', 'holder' => 'Arif Aerial'],
            ['name' => 'Sound Line Array Palembang', 'category' => 'sound-system-audio', 'pic' => 'Eko Audio', 'phone' => '81273181006', 'address' => 'Jl. Kol. Atmo, Palembang', 'description' => 'Line array plus operator.', 'harga_publish' => 6800000, 'harga_vendor' => 5400000, 'bank' => 'BNI', 'account' => '9051111006', 'holder' => 'Eko Audio Visual'],
            ['name' => 'MUA Pengantin Palembang', 'category' => 'make-up-beauty', 'pic' => 'Fitri Makeup', 'phone' => '81273181007', 'address' => 'Jl. Angkatan 45, Palembang', 'description' => 'Makeup akad & resepsi, trial, touch up.', 'harga_publish' => 3200000, 'harga_vendor' => 2500000, 'bank' => 'BCA', 'account' => '9051111007', 'holder' => 'Fitri Makeup Artist'],
            ['name' => 'Aesan Gede Styling', 'category' => 'make-up-beauty', 'pic' => 'Kartika Aesan', 'phone' => '81273181008', 'address' => 'Jl. Merdeka, Palembang', 'description' => 'Aesan gede, songket, dan aksesoris adat.', 'harga_publish' => 8500000, 'harga_vendor' => 6800000, 'bank' => 'Bank Sumsel Babel', 'account' => '9051111008', 'holder' => 'Kartika Aesan House'],
            ['name' => 'MC Bilingual Palembang', 'category' => 'entertainment-mc', 'pic' => 'Andre MC', 'phone' => '81273181009', 'address' => 'Jl. Radial, Palembang', 'description' => 'MC Indonesia-English untuk akad dan resepsi.', 'harga_publish' => 3500000, 'harga_vendor' => 2800000, 'bank' => 'Mandiri', 'account' => '9051111009', 'holder' => 'Andre MC'],
            ['name' => 'Ballroom Aryaduta Palembang', 'category' => 'venue-gedung', 'pic' => 'Dewi Venue', 'phone' => '81273181010', 'address' => 'Jl. Kapten A. Rivai, Palembang', 'description' => 'Ballroom hotel 300-600 pax.', 'harga_publish' => 38000000, 'harga_vendor' => 32000000, 'bank' => 'BCA', 'account' => '9051111010', 'holder' => 'Aryaduta Palembang'],
            ['name' => 'Garden Jakabaring', 'category' => 'venue-gedung', 'pic' => 'Bambang Garden', 'phone' => '81273181011', 'address' => 'Jakabaring Sport City, Palembang', 'description' => 'Garden outdoor plus indoor backup.', 'harga_publish' => 18000000, 'harga_vendor' => 14500000, 'bank' => 'BNI', 'account' => '9051111011', 'holder' => 'Jakabaring Garden'],
            ['name' => 'WO Day Coordinator Palembang', 'category' => 'wedding-organizer', 'pic' => 'Tia Coordinator', 'phone' => '81273181012', 'address' => 'Jl. Sudirman, Palembang', 'description' => 'Koordinasi hari-H dan rundown.', 'harga_publish' => 4500000, 'harga_vendor' => 3600000, 'bank' => 'Mandiri', 'account' => '9051111012', 'holder' => 'Tia Coordinator'],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function productDefinitions(): array
    {
        return [
            [
                'name' => 'Paket Intimate Palembang 150 Pax',
                'category' => 'wedding-organizer',
                'pax' => 150,
                'pengurangan' => 1500000,
                'description' => 'Paket intimate: MUA, MC, dan koordinator hari-H.',
                'vendors' => ['rywo-mua-pengantin-palembang', 'rywo-mc-bilingual-palembang', 'rywo-wo-day-coordinator-palembang'],
            ],
            [
                'name' => 'Paket Garden Jakabaring 200 Pax',
                'category' => 'venue-gedung',
                'pax' => 200,
                'pengurangan' => 2000000,
                'description' => 'Garden Jakabaring dengan pelaminan songket dan catering.',
                'vendors' => ['rywo-garden-jakabaring', 'rywo-pelaminan-songket-palembang', 'rywo-catering-pempek-prasmanan'],
            ],
            [
                'name' => 'Paket Ballroom Aryaduta 300 Pax',
                'category' => 'venue-gedung',
                'pax' => 300,
                'pengurangan' => 2500000,
                'description' => 'Ballroom hotel, lighting, dan dokumentasi cinematic.',
                'vendors' => ['rywo-ballroom-aryaduta-palembang', 'rywo-foto-cinematic-palembang', 'rywo-sound-line-array-palembang'],
            ],
            [
                'name' => 'Paket Adat Palembang 350 Pax',
                'category' => 'make-up-beauty',
                'pax' => 350,
                'pengurangan' => 0,
                'description' => 'Aesan gede, pelaminan songket, dan catering pempek.',
                'vendors' => ['rywo-aesan-gede-styling', 'rywo-pelaminan-songket-palembang', 'rywo-catering-pempek-prasmanan'],
            ],
            [
                'name' => 'Paket Sunset Musi 180 Pax',
                'category' => 'foto-video',
                'pax' => 180,
                'pengurangan' => 1800000,
                'description' => 'Dokumentasi drone Ampera, florist, dan cinematic.',
                'vendors' => ['rywo-drone-sungai-musi', 'rywo-florist-musi-fresh', 'rywo-foto-cinematic-palembang'],
            ],
            [
                'name' => 'Paket Premium Palembang 400 Pax',
                'category' => 'venue-gedung',
                'pax' => 400,
                'pengurangan' => 4000000,
                'description' => 'Ballroom Aryaduta, catering besar, dan full koordinator.',
                'vendors' => ['rywo-ballroom-aryaduta-palembang', 'rywo-catering-pempek-prasmanan', 'rywo-wo-day-coordinator-palembang', 'rywo-sound-line-array-palembang'],
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function prospectDefinitions(): array
    {
        return [
            ['name_event' => 'Wedding Andi & Sari Palembang', 'cpp' => 'Andi Pratama', 'cpw' => 'Sari Dewi', 'venue' => 'Rumah Keluarga, Palembang', 'phone' => '81273182001', 'address' => 'Jl. Merdeka No. 10, Palembang', 'penawaran' => 48000000, 'notes' => 'Intimate 150 pax, tema ivory.'],
            ['name_event' => 'Wedding Budi & Maya Jakabaring', 'cpp' => 'Budi Santoso', 'cpw' => 'Maya Putri', 'venue' => 'Garden Jakabaring', 'phone' => '81273182002', 'address' => 'Jakabaring, Palembang', 'penawaran' => 76000000, 'notes' => 'Garden outdoor, backup indoor.'],
            ['name_event' => 'Wedding Dedi & Rina Aryaduta', 'cpp' => 'Dedi Kurniawan', 'cpw' => 'Rina Sari', 'venue' => 'Ballroom Aryaduta Palembang', 'phone' => '81273182003', 'address' => 'Jl. Kapten A. Rivai, Palembang', 'penawaran' => 128000000, 'notes' => 'Resepsi ballroom 300 pax.'],
            ['name_event' => 'Wedding Eko & Fitri Adat', 'cpp' => 'Eko Prasetyo', 'cpw' => 'Fitri Handayani', 'venue' => 'Rumah Limas Palembang', 'phone' => '81273182004', 'address' => 'Jl. Ki Merogan, Palembang', 'penawaran' => 98000000, 'notes' => 'Adat Palembang, aesan gede.'],
            ['name_event' => 'Wedding Fajar & Indira Musi', 'cpp' => 'Fajar Nugroho', 'cpw' => 'Indira Salsabila', 'venue' => 'Tepian Musi / Ampera', 'phone' => '81273182005', 'address' => 'Jl. Jend. Sudirman, Palembang', 'penawaran' => 72000000, 'notes' => 'Sunset Ampera, drone wajib.'],
            ['name_event' => 'Wedding Galih & Hana', 'cpp' => 'Galih Ramadhan', 'cpw' => 'Hana Pertiwi', 'venue' => 'Hotel Aryaduta', 'phone' => '81273182006', 'address' => 'Palembang', 'penawaran' => 88000000, 'notes' => 'Masih simulasi, belum closing.'],
            ['name_event' => 'Wedding Ivan & Julia', 'cpp' => 'Ivan Kurniawan', 'cpw' => 'Julia Maharani', 'venue' => 'Garden Jakabaring', 'phone' => '81273182007', 'address' => 'Palembang', 'penawaran' => 69000000, 'notes' => 'Hangat, menunggu konfirmasi venue.'],
            ['name_event' => 'Wedding Khalil & Lina', 'cpp' => 'Khalil Ahmad', 'cpw' => 'Lina Safitri', 'venue' => 'Rumah Limas', 'phone' => '81273182008', 'address' => 'Palembang', 'penawaran' => 81000000, 'notes' => 'Prospek adat, belum order.'],
        ];
    }
}
