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
                        'label' => 'Hingga 10 vendor',
                        'tip' => 'Batas master vendor per tim. Setelah penuh, tambah vendor baru dikunci sampai upgrade.',
                    ],
                    [
                        'label' => 'Hingga 10 produk',
                        'tip' => 'Batas katalog produk/paket wedding yang bisa dikelola di sistem.',
                    ],
                    [
                        'label' => 'Hingga 10 proyek wedding',
                        'tip' => 'Batas order/proyek wedding aktif yang bisa dibuat dalam paket ini.',
                    ],
                    [
                        'label' => 'Hingga 30 prospek',
                        'tip' => 'Batas data prospek/lead yang bisa disimpan untuk pipeline penjualan.',
                    ],
                    [
                        'label' => 'Hingga 20 simulasi',
                        'tip' => 'Batas simulasi paket yang bisa dibuat untuk penawaran ke calon klien.',
                    ],
                    [
                        'label' => 'Manajemen proyek wedding',
                        'tip' => 'Kelola order, prospek, simulasi, produk, vendor, dan kategori dalam satu alur kerja.',
                    ],
                    [
                        'label' => 'Keuangan dasar',
                        'tip' => 'Pendapatan & pengeluaran wedding, piutang, aset tetap, dan daftar rekening — tanpa rekonsiliasi bank.',
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
                        'label' => 'Hingga 50 vendor',
                        'tip' => 'Kuota vendor lebih besar untuk WO dengan banyak mitra vendor per proyek.',
                    ],
                    [
                        'label' => 'Hingga 50 produk',
                        'tip' => 'Katalog produk/paket hingga 50 item per tim.',
                    ],
                    [
                        'label' => 'Hingga 50 proyek · 150 prospek · 100 simulasi',
                        'tip' => 'Kuota penjualan: 50 proyek wedding, 150 prospek, dan 100 simulasi paket.',
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
                        'label' => 'Hingga 200 vendor',
                        'tip' => 'Kuota vendor besar untuk WO dengan volume proyek tinggi.',
                    ],
                    [
                        'label' => 'Hingga 200 produk',
                        'tip' => 'Katalog produk/paket hingga 200 item per tim.',
                    ],
                    [
                        'label' => 'Hingga 200 proyek · 500 prospek · 400 simulasi',
                        'tip' => 'Kuota penjualan: 200 proyek wedding, 500 prospek, dan 400 simulasi paket.',
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
     * Batas kuota resource: users | vendors | products | orders | prospects | simulasi.
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
