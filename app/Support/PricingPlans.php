<?php

namespace App\Support;

class PricingPlans
{
    /**
     * Fitur yang digating per paket.
     */
    public const FEATURE_HRIS = 'hris';

    public const FEATURE_RECONCILIATION = 'reconciliation';

    public const FEATURE_PAYROLL = 'payroll';

    public const FEATURE_ROLE_MANAGEMENT = 'role_management';

    public const FEATURE_ADVANCED_REPORTS = 'advanced_reports';

    public const FEATURE_MULTI_APPROVAL = 'multi_approval';

    public const FEATURE_EMPLOYEE_PORTAL = 'employee_portal';

    public const FEATURE_PROJECTS = 'projects';

    public const FEATURE_BASIC_FINANCE = 'basic_finance';

    public const FEATURE_NOTA_DINAS = 'nota_dinas';

    public const FEATURE_DOCUMENTS = 'documents';

    /**
     * Paket layanan WOFINS — sumber tunggal untuk halaman Harga,
     * formulir pendaftaran, kontak, email, Filament, dan feature gate.
     *
     * @return list<array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            [
                'key' => 'starter',
                'name' => 'Starter',
                'desc' => 'Cocok untuk WO yang baru mulai merapikan operasional.',
                'price' => '99',
                'unit' => 'RB',
                'price_monthly' => 99_000,
                'price_annual' => 1_089_000, // 11 bulan (hemat 1 bulan)
                'seat_limit' => 3,
                'vendor_limit' => 10,
                'product_limit' => 10,
                'order_limit' => 10,
                'prospect_limit' => 30,
                'simulasi_limit' => 20,
                'payment_method_limit' => 2,
                'fixed_asset_limit' => 5,
                'piutang_limit' => 20,
                'pembayaran_piutang_limit' => 50,
                'category_limit' => 10,
                'data_pembayaran_limit' => 100,
                'expense_limit' => 100,
                'expense_ops_limit' => 50,
                'pendapatan_lain_limit' => 30,
                'pengeluaran_lain_limit' => 30,
                'popular' => false,
                'cta' => 'Pilih Paket Starter',
                'cta_class' => 'wf-btn-ghost',
                'check' => 'navy',
                'features' => [
                    [
                        'label' => 'Hingga 3 pengguna',
                        'tip' => 'Maksimal 3 akun tim aktif (selain super admin platform). Cocok untuk owner + AM + finance.',
                    ],
                    [
                        'label' => 'Hingga 10 kategori · 10 vendor · 10 produk',
                        'tip' => 'Kuota katalog: 10 kategori, 10 vendor, dan 10 produk/paket wedding per perusahaan.',
                    ],
                    [
                        'label' => 'Hingga 10 proyek · 30 prospek · 20 simulasi',
                        'tip' => 'Kuota penjualan: 10 proyek wedding, 30 prospek, dan 20 simulasi paket.',
                    ],
                    [
                        'label' => 'Hingga 2 rekening · 5 aset tetap',
                        'tip' => '2 rekening bank/kas dan 5 aset tetap per perusahaan.',
                    ],
                    [
                        'label' => 'Hingga 20 piutang · 50 pembayaran piutang',
                        'tip' => 'Kuota catatan piutang dan transaksi pembayaran piutang dalam paket Starter.',
                    ],
                    [
                        'label' => 'Hingga 100 pendapatan & 100 pengeluaran wedding',
                        'tip' => 'Kuota transaksi kas proyek (pembayaran klien & biaya vendor) per perusahaan.',
                    ],
                    [
                        'label' => 'Hingga 50 ops · 30 pendapatan/pengeluaran lain',
                        'tip' => 'Kuota kas operasional dan transaksi non-proyek.',
                    ],
                    [
                        'label' => 'Manajemen proyek wedding',
                        'tip' => 'Kelola order, prospek, simulasi, produk, vendor, dan kategori dalam satu alur kerja.',
                    ],
                    [
                        'label' => 'Keuangan dasar',
                        'tip' => 'Kas proyek & operasional, piutang, aset tetap, dan daftar rekening — tanpa rekonsiliasi bank.',
                    ],
                ],
                'feature_keys' => [
                    self::FEATURE_PROJECTS,
                    self::FEATURE_BASIC_FINANCE,
                ],
                'selectable' => true,
            ],
            [
                'key' => 'professional',
                'name' => 'Professional',
                'desc' => 'Paling cocok untuk WO yang ingin kendali proyek, kas, dan nota dinas sehari-hari.',
                'price' => '180',
                'unit' => 'RB',
                'price_monthly' => 180_000,
                'price_annual' => 1_980_000, // 11 bulan (hemat 1 bulan)
                'seat_limit' => 10,
                'vendor_limit' => 50,
                'product_limit' => 50,
                'order_limit' => 50,
                'prospect_limit' => 150,
                'simulasi_limit' => 100,
                'payment_method_limit' => 5,
                'fixed_asset_limit' => 25,
                'piutang_limit' => 100,
                'pembayaran_piutang_limit' => 300,
                'category_limit' => 40,
                'data_pembayaran_limit' => 500,
                'expense_limit' => 500,
                'expense_ops_limit' => 300,
                'pendapatan_lain_limit' => 150,
                'pengeluaran_lain_limit' => 150,
                'popular' => true,
                'cta' => 'Pilih Paket Professional',
                'cta_class' => 'wf-btn-gold',
                'check' => '',
                'features' => [
                    [
                        'label' => 'Hingga 10 pengguna',
                        'tip' => 'Maksimal 10 akun tim aktif — ruang untuk owner, AM, finance, dan staf operasional.',
                    ],
                    [
                        'label' => 'Hingga 40 kategori · 50 vendor · 50 produk',
                        'tip' => 'Kuota katalog lebih besar: 40 kategori, 50 vendor, dan 50 produk/paket per perusahaan.',
                    ],
                    [
                        'label' => 'Hingga 50 proyek · 150 prospek · 100 simulasi',
                        'tip' => 'Kuota penjualan: 50 proyek wedding, 150 prospek, dan 100 simulasi paket.',
                    ],
                    [
                        'label' => 'Hingga 5 rekening · 25 aset tetap',
                        'tip' => '5 rekening bank/kas dan 25 aset tetap — cocok multi-bank atau kas terpisah.',
                    ],
                    [
                        'label' => 'Hingga 100 piutang · 300 pembayaran piutang',
                        'tip' => 'Kuota piutang dan pembayaran piutang lebih longgar untuk volume transaksi harian.',
                    ],
                    [
                        'label' => 'Hingga 500 pendapatan & 500 pengeluaran wedding',
                        'tip' => 'Kuota transaksi kas proyek untuk volume pembayaran & biaya vendor yang lebih tinggi.',
                    ],
                    [
                        'label' => 'Hingga 300 ops · 150 pendapatan/pengeluaran lain',
                        'tip' => 'Kuota kas operasional dan transaksi non-proyek paket Professional.',
                    ],
                    [
                        'label' => 'Semua fitur Starter',
                        'tip' => 'Termasuk manajemen proyek wedding dan keuangan dasar dari paket Starter.',
                    ],
                    [
                        'label' => 'Nota dinas digital',
                        'tip' => 'Ajukan, setujui, dan lacak nota dinas beserta detail transfer & lampiran secara digital.',
                    ],
                    [
                        'label' => 'Rekonsiliasi rekening koran',
                        'tip' => 'Import rekening koran, cocokkan transaksi otomatis, dan unduh PDF rekonsiliasi.',
                    ],
                    [
                        'label' => 'Payroll dasar',
                        'tip' => 'Kelola payroll dan slip gaji digital untuk tim inti (tanpa modul HRIS penuh).',
                    ],
                    [
                        'label' => 'Support prioritas',
                        'tip' => 'Antrian bantuan lebih cepat dibanding Starter, termasuk dampingan setup awal.',
                    ],
                ],
                'feature_keys' => [
                    self::FEATURE_PROJECTS,
                    self::FEATURE_BASIC_FINANCE,
                    self::FEATURE_NOTA_DINAS,
                    self::FEATURE_RECONCILIATION,
                    self::FEATURE_PAYROLL,
                ],
                'selectable' => true,
            ],
            [
                'key' => 'business',
                'name' => 'Business',
                'desc' => 'Untuk WO dengan banyak proyek dan tim lintas fungsi.',
                'price' => '295',
                'unit' => 'RB',
                'price_monthly' => 295_000,
                'price_annual' => 3_245_000, // 11 bulan (hemat 1 bulan)
                'seat_limit' => 25,
                'vendor_limit' => 200,
                'product_limit' => 200,
                'order_limit' => 200,
                'prospect_limit' => 500,
                'simulasi_limit' => 400,
                'payment_method_limit' => 15,
                'fixed_asset_limit' => 100,
                'piutang_limit' => 500,
                'pembayaran_piutang_limit' => 2000,
                'category_limit' => 100,
                'data_pembayaran_limit' => 2000,
                'expense_limit' => 2000,
                'expense_ops_limit' => 1000,
                'pendapatan_lain_limit' => 500,
                'pengeluaran_lain_limit' => 500,
                'popular' => false,
                'cta' => 'Pilih Paket Business',
                'cta_class' => 'wf-btn-ghost',
                'check' => 'navy',
                'features' => [
                    [
                        'label' => 'Hingga 25 pengguna',
                        'tip' => 'Maksimal 25 akun tim — cocok untuk finance, HRD, AM, dan staf lintas fungsi.',
                    ],
                    [
                        'label' => 'Hingga 100 kategori · 200 vendor · 200 produk',
                        'tip' => 'Kuota katalog besar: 100 kategori, 200 vendor, dan 200 produk/paket per perusahaan.',
                    ],
                    [
                        'label' => 'Hingga 200 proyek · 500 prospek · 400 simulasi',
                        'tip' => 'Kuota penjualan: 200 proyek wedding, 500 prospek, dan 400 simulasi paket.',
                    ],
                    [
                        'label' => 'Hingga 15 rekening · 100 aset tetap',
                        'tip' => '15 rekening bank/kas dan 100 aset tetap untuk WO dengan banyak unit kas.',
                    ],
                    [
                        'label' => 'Hingga 500 piutang · 2.000 pembayaran piutang',
                        'tip' => 'Kuota piutang dan pembayaran piutang untuk volume transaksi tinggi.',
                    ],
                    [
                        'label' => 'Hingga 2.000 pendapatan & 2.000 pengeluaran wedding',
                        'tip' => 'Kuota transaksi kas proyek untuk WO dengan volume tinggi.',
                    ],
                    [
                        'label' => 'Hingga 1.000 ops · 500 pendapatan/pengeluaran lain',
                        'tip' => 'Kuota kas operasional dan transaksi non-proyek paket Business.',
                    ],
                    [
                        'label' => 'Semua fitur Professional',
                        'tip' => 'Termasuk nota dinas, rekonsiliasi rekening, payroll dasar, dan seluruh kapasitas Professional.',
                    ],
                    [
                        'label' => 'Dokumen & SOP',
                        'tip' => 'Simpan dokumen resmi, SOP perusahaan, dan knowledge base tim dalam satu tempat.',
                    ],
                    [
                        'label' => 'Domain gratis',
                        'tip' => 'Termasuk domain (.com / .id sesuai ketersediaan) selama masa berlangganan Business aktif.',
                    ],
                    [
                        'label' => 'HRIS & absensi GPS',
                        'tip' => 'Absensi GPS + foto + geofence, jadwal kerja, koreksi, lembur, cuti, dan data karyawan.',
                    ],
                    [
                        'label' => 'Payroll & portal karyawan',
                        'tip' => 'Payroll lengkap plus portal karyawan (ESS) agar staf bisa absen/cuti tanpa akses admin penuh.',
                    ],
                    [
                        'label' => 'Laporan lanjutan',
                        'tip' => 'Laporan operasional lebih dalam, termasuk arus kas bersih dan target AM.',
                    ],
                    [
                        'label' => 'Multi-approval workflow',
                        'tip' => 'Alur persetujuan bertingkat untuk nota dinas, dokumen, atau permintaan staf.',
                    ],
                    [
                        'label' => 'Manajemen role & permission',
                        'tip' => 'Kelola role Spatie/Filament Shield untuk membagi akses tim secara detail.',
                    ],
                    [
                        'label' => 'Onboarding & training tim',
                        'tip' => 'Dampingan go-live dan training agar seluruh tim siap memakai sistem.',
                    ],
                    [
                        'label' => 'Support WhatsApp',
                        'tip' => 'Bantuan langsung via WhatsApp selama masa berlangganan aktif.',
                    ],
                ],
                'feature_keys' => [
                    self::FEATURE_PROJECTS,
                    self::FEATURE_BASIC_FINANCE,
                    self::FEATURE_NOTA_DINAS,
                    self::FEATURE_DOCUMENTS,
                    self::FEATURE_RECONCILIATION,
                    self::FEATURE_HRIS,
                    self::FEATURE_PAYROLL,
                    self::FEATURE_EMPLOYEE_PORTAL,
                    self::FEATURE_ADVANCED_REPORTS,
                    self::FEATURE_MULTI_APPROVAL,
                    self::FEATURE_ROLE_MANAGEMENT,
                ],
                'selectable' => true,
            ],
            // Enterprise tidak ditawarkan di aplikasi ini — produk terpisah.
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(?string $key): ?array
    {
        if (! filled($key)) {
            return null;
        }

        foreach (static::all() as $plan) {
            if ($plan['key'] === $key) {
                return $plan;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public static function selectableKeys(): array
    {
        return collect(static::all())
            ->where('selectable', true)
            ->pluck('key')
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public static function selectOptions(): array
    {
        $options = [];

        foreach (static::all() as $plan) {
            if (! $plan['selectable']) {
                continue;
            }

            $options[$plan['key']] = static::optionLabel($plan['key']);
        }

        return $options;
    }

    /**
     * Opsi Select Filament ProspectApp.
     * Legacy Hastana hanya ikut jika record sudah memakai key itu.
     *
     * @return array<string, string>
     */
    public static function filamentOptions(?string $currentKey = null): array
    {
        $options = [
            ...static::selectOptions(),
            'lain_lain' => 'Lain-lain (Custom)',
        ];

        $legacy = static::legacyServiceLabels();

        if ($currentKey && isset($legacy[$currentKey])) {
            $options[$currentKey] = $legacy[$currentKey];
        }

        return $options;
    }

    /**
     * Opsi filter tabel (termasuk legacy agar data lama bisa difilter).
     *
     * @return array<string, string>
     */
    public static function filamentFilterOptions(): array
    {
        return [
            ...static::selectOptions(),
            'lain_lain' => 'Lain-lain (Custom)',
            ...static::legacyServiceLabels(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function legacyServiceLabels(): array
    {
        return [
            'hastana' => 'Paket Anggota Hastana (lama)',
            'non_hastana' => 'Paket Non Hastana (lama)',
        ];
    }

    /**
     * Opsi paket untuk Company form (tanpa legacy prospect).
     *
     * @return array<string, string>
     */
    public static function companyPlanOptions(): array
    {
        $options = [];

        foreach (static::all() as $plan) {
            $parts = [];
            foreach ([
                'seat_limit' => 'user',
                'vendor_limit' => 'vendor',
                'product_limit' => 'produk',
                'order_limit' => 'proyek',
                'prospect_limit' => 'prospek',
                'simulasi_limit' => 'simulasi',
                'payment_method_limit' => 'rekening',
                'fixed_asset_limit' => 'aset',
                'piutang_limit' => 'piutang',
                'pembayaran_piutang_limit' => 'bayar piutang',
                'category_limit' => 'kategori',
                'data_pembayaran_limit' => 'pendapatan wedding',
                'expense_limit' => 'pengeluaran wedding',
                'expense_ops_limit' => 'ops',
                'pendapatan_lain_limit' => 'pendapatan lain',
                'pengeluaran_lain_limit' => 'pengeluaran lain',
            ] as $key => $label) {
                $limit = $plan[$key] ?? null;
                $parts[] = $limit === null ? "{$label} ∞" : "{$limit} {$label}";
            }
            $options[$plan['key']] = $plan['name'].' ('.implode(', ', $parts).')';
        }

        return $options;
    }

    public static function optionLabel(?string $key): string
    {
        $plan = static::find($key);

        if ($plan) {
            if ($plan['price']) {
                return "Paket {$plan['name']} — Rp {$plan['price']} {$plan['unit']} / bulan";
            }

            return "Paket {$plan['name']} — Hubungi Kami";
        }

        return match ($key) {
            'hastana' => 'Paket Anggota Hastana (lama)',
            'non_hastana' => 'Paket Non Hastana (lama)',
            'lain_lain' => 'Lain-lain (Custom)',
            default => filled($key) ? (string) $key : '—',
        };
    }

    public static function shortLabel(?string $key): string
    {
        $plan = static::find($key);

        if ($plan) {
            return 'Paket '.$plan['name'];
        }

        return match ($key) {
            'hastana' => 'Paket Hastana',
            'non_hastana' => 'Paket Non Hastana',
            'lain_lain' => 'Lain-lain',
            'enterprise' => 'Enterprise (produk terpisah)',
            default => filled($key) ? ucfirst((string) $key) : '—',
        };
    }

    public static function priceDisplay(?string $key): ?string
    {
        $plan = static::find($key);

        if (! $plan) {
            return null;
        }

        if ($plan['price']) {
            return 'Rp '.$plan['price'].' '.$plan['unit'].'/bulan';
        }

        return 'Hubungi Kami';
    }

    public static function annualAmount(?string $key): ?int
    {
        $plan = static::find($key);

        if ($plan && $plan['price_annual'] !== null) {
            return (int) $plan['price_annual'];
        }

        return match ($key) {
            'hastana' => 8_500_000,
            'non_hastana' => 10_000_000,
            default => null,
        };
    }

    /**
     * @return array<string, int>
     */
    public static function priceMap(): array
    {
        $map = [];

        foreach (static::all() as $plan) {
            if ($plan['price_annual'] !== null) {
                $map[$plan['key']] = $plan['price_annual'];
            }
        }

        $map['hastana'] = 8_500_000;
        $map['non_hastana'] = 10_000_000;

        return $map;
    }

    /**
     * Batas kuota resource: users | vendors | products | orders | prospects | simulasi |
     * categories | payment_methods | fixed_assets | piutangs | pembayaran_piutangs.
     * null = tak terbatas.
     */
    public static function limit(?string $planKey, string $resource): ?int
    {
        $plan = static::find($planKey) ?? static::find('starter');
        $column = match ($resource) {
            'users', 'seats' => 'seat_limit',
            'vendors' => 'vendor_limit',
            'products' => 'product_limit',
            'orders' => 'order_limit',
            'prospects' => 'prospect_limit',
            'simulasi', 'simulations' => 'simulasi_limit',
            'payment_methods', 'rekening' => 'payment_method_limit',
            'fixed_assets', 'aset' => 'fixed_asset_limit',
            'piutangs', 'piutang' => 'piutang_limit',
            'pembayaran_piutangs', 'pembayaran_piutang' => 'pembayaran_piutang_limit',
            'categories', 'kategori' => 'category_limit',
            'data_pembayarans', 'pendapatan_wedding' => 'data_pembayaran_limit',
            'expenses', 'pengeluaran_wedding' => 'expense_limit',
            'expense_ops', 'pengeluaran_ops' => 'expense_ops_limit',
            'pendapatan_lains', 'pendapatan_lain' => 'pendapatan_lain_limit',
            'pengeluaran_lains', 'pengeluaran_lain' => 'pengeluaran_lain_limit',
            default => null,
        };

        if (! $column || ! $plan) {
            return 0;
        }

        $value = $plan[$column] ?? null;

        return $value === null ? null : (int) $value;
    }

    /**
     * Batas kursi paket (null = tak terbatas).
     */
    public static function seatLimit(?string $key): ?int
    {
        return static::limit($key, 'users');
    }

    /**
     * Normalisasi daftar fitur kartu harga (label + tip opsional).
     *
     * @param  array<string, mixed>  $plan
     * @return list<array{label: string, tip: ?string}>
     */
    public static function featureItems(array $plan): array
    {
        $items = [];

        foreach ($plan['features'] ?? [] as $feature) {
            if (is_string($feature)) {
                $items[] = ['label' => $feature, 'tip' => null];

                continue;
            }

            if (! is_array($feature)) {
                continue;
            }

            $label = trim((string) ($feature['label'] ?? ''));

            if ($label === '') {
                continue;
            }

            $tip = $feature['tip'] ?? null;
            $tip = is_string($tip) && trim($tip) !== '' ? trim($tip) : null;

            $items[] = ['label' => $label, 'tip' => $tip];
        }

        return $items;
    }

    /**
     * Apakah paket mengizinkan fitur tertentu.
     */
    public static function allows(?string $planKey, string $feature): bool
    {
        $plan = static::find($planKey);

        if (! $plan) {
            // Tanpa paket dikenal: hanya fitur starter dasar
            $plan = static::find('starter');
        }

        $keys = $plan['feature_keys'] ?? [];

        return in_array($feature, $keys, true);
    }

    /**
     * Normalisasi key paket (prospect / query).
     */
    public static function normalizeKey(?string $key): ?string
    {
        if (! filled($key)) {
            return null;
        }

        $key = str_replace('-', '_', strtolower(trim($key)));

        return match ($key) {
            'hastana', 'non_hastana', 'nonhastana' => 'professional', // legacy → Professional
            'starter', 'professional', 'business' => $key,
            'enterprise' => null, // produk terpisah — tidak ditawarkan di app ini
            'lain_lain' => null,
            default => static::find($key) ? $key : null,
        };
    }
}
