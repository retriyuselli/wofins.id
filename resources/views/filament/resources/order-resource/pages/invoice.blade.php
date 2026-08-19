@php
    $companyName = $companyName ?? config('app.name');
    $companyAddress = $companyAddress ?? null;
    $companyPhone = $companyPhone ?? null;
    $companyEmail = $companyEmail ?? null;
    $companyWebsite = $companyWebsite ?? null;
    $companyLogoUrl = $companyLogoUrl ?? null;
    $logoSrc = $companyLogoUrl ?: '';

    $phone = $order->prospect->phone ?? '';
    $whatsappUrl = '#';
    if ($phone) {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (substr($phone, 0, 1) === '0') {
            $phone = '62'.$phone;
        } elseif (substr($phone, 0, 1) === '8') {
            $phone = '62'.$phone;
        }
        $message = 'Halo, berikut adalah invoice Anda: '.route('invoice.download', ['order' => $order]);
        $whatsappUrl = 'https://wa.me/'.$phone.'?text='.urlencode($message);
    }

    $grandTotal = $order->grand_total ?? 0;
    $totalPaid = $order->bayar ?? 0;
    $balanceDue = $order->sisa ?? 0;
    $paymentProgress = $grandTotal > 0 ? ($totalPaid / $grandTotal) * 100 : 0;
    $paymentProgress = min($paymentProgress, 100);
    $eventName = $order->prospect->name_event ?? null;
@endphp

<x-filament-panels::page>
    {{-- Tema sama dengan Detail Rekening (navy / gold / cream / Poppins) — scoped di .pm-wf --}}
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/payment/paymentmethod.css') }}?v={{ @filemtime(public_path('assets/payment/paymentmethod.css')) }}">

    <style>
        .pm-wf .pm-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            padding: 0.55rem 1rem;
            border-radius: 999px;
            font-size: 0.8125rem;
            font-weight: 600;
            text-decoration: none;
            border: 1px solid transparent;
            transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
        }

        .pm-wf .pm-btn--gold {
            background: var(--wf-gold);
            color: var(--wf-navy-deep);
        }

        .pm-wf .pm-btn--gold:hover {
            background: var(--wf-gold-soft);
        }

        .pm-wf .pm-btn--cream {
            background: rgba(247, 244, 238, 0.14);
            color: var(--wf-cream);
            border-color: rgba(247, 244, 238, 0.35);
        }

        .pm-wf .pm-btn--cream:hover {
            background: rgba(247, 244, 238, 0.24);
        }

        .pm-wf .pm-hero__actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            justify-content: flex-end;
            margin-top: 0.85rem;
        }

        .pm-wf .inv-office {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            padding-bottom: 1rem;
            margin-bottom: 1.25rem;
            border-bottom: 1px solid var(--wf-line);
        }

        .pm-wf .inv-office__label {
            margin: 0;
            font-size: 0.72rem;
            font-weight: 600;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--wf-gold);
        }

        .pm-wf .inv-office address {
            margin: 0.35rem 0 0;
            font-style: normal;
            line-height: 1.5;
            font-size: 0.875rem;
            color: var(--wf-ink);
            overflow-wrap: anywhere;
        }

        .pm-wf .inv-office__logo img {
            display: block;
            max-height: 64px;
            width: auto;
            max-width: 160px;
            margin-left: auto;
            object-fit: contain;
        }

        .pm-wf .progress-bar-container {
            margin: 1.5rem 0;
        }

        .pm-wf .progress-bar-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.35rem;
            font-size: 0.875rem;
        }

        .pm-wf .progress-bar-label {
            font-weight: 500;
            color: var(--wf-muted);
        }

        .pm-wf .progress-bar-track {
            height: 0.55rem;
            border-radius: 999px;
            overflow: hidden;
            border: 1px solid var(--wf-line);
            background: var(--wf-cream);
        }

        .pm-wf .progress-bar-fill {
            height: 100%;
            border-radius: 999px;
        }
    </style>

    <div class="pm-wf space-y-6">
        <header class="pm-hero">
            <div class="pm-hero__shapes" aria-hidden="true">
                <span class="pm-shape pm-shape--blob pm-shape--l1"></span>
                <span class="pm-shape pm-shape--ring pm-shape--l2"></span>
                <span class="pm-shape pm-shape--square pm-shape--l3"></span>
                <span class="pm-shape pm-shape--dot pm-shape--l4"></span>
                <span class="pm-shape pm-shape--tri pm-shape--l5"></span>
                <span class="pm-shape pm-shape--blob pm-shape--r1"></span>
                <span class="pm-shape pm-shape--ring pm-shape--r2"></span>
                <span class="pm-shape pm-shape--square pm-shape--r3"></span>
                <span class="pm-shape pm-shape--dot pm-shape--r4"></span>
                <span class="pm-shape pm-shape--blob pm-shape--r5"></span>
                <span class="pm-shape pm-shape--blob pm-shape--t1"></span>
                <span class="pm-shape pm-shape--blob pm-shape--b1"></span>
            </div>
            <div class="pm-hero__glow" aria-hidden="true"></div>
            <div class="pm-hero__body">
                <div class="pm-hero__meta">
                    <p class="pm-hero__eyebrow">Invoice Order</p>
                    <p class="pm-hero__company">{{ $companyName }}</p>
                    <h1 class="pm-hero__title">DETAILS #{{ $order->number ?? $order->id }}</h1>
                    <p class="pm-hero__sub">
                        <span>{{ $order->created_at?->format('d M Y') }}</span>
                        @if ($eventName)
                            <span class="pm-hero__dot">·</span>
                            <span>{{ $eventName }}</span>
                        @endif
                    </p>
                    <div class="pm-hero__badges">
                        @if ($order->is_paid)
                            <span class="pm-badge pm-badge--gold">Paid</span>
                        @else
                            <span class="pm-badge pm-badge--cream">Unpaid</span>
                        @endif
                        <span class="pm-badge pm-badge--outline">{{ $companyName }}</span>
                    </div>
                </div>

                <div class="pm-hero__balance">
                    <p class="pm-hero__balance-label">Sisa Tagihan</p>
                    <p @class([
                        'pm-hero__balance-value',
                        'is-positive' => $balanceDue <= 0,
                        'is-negative' => $balanceDue > 0,
                    ])>
                        Rp {{ number_format($balanceDue, 0, ',', '.') }}
                    </p>
                    <p class="pm-hero__balance-delta is-flat">
                        Dibayar Rp {{ number_format($totalPaid, 0, ',', '.') }}
                        dari Rp {{ number_format($grandTotal, 0, ',', '.') }}
                    </p>
                    <div class="pm-hero__actions">
                        @if ($phone)
                            <a href="{{ $whatsappUrl }}" target="_blank" class="pm-btn pm-btn--cream">WhatsApp</a>
                        @endif
                        <a href="{{ route('invoice.download', ['order' => $order]) }}" target="_blank" class="pm-btn pm-btn--gold">
                            Download Invoice
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <div class="pm-tabs-shell">
            <div class="pm-panel">
                <div class="inv-office">
                    <div style="min-width: 0; flex: 1 1 auto;">
                        <p class="inv-office__label">Office Information</p>
                        <address>
                            <strong>{{ $companyName }}</strong><br>
                            {{ $companyAddress ?: 'Alamat belum diatur' }}
                            · Phone: {{ $companyPhone ?: '-' }}
                            @if (! empty($companyEmail))
                                <br>Email: {{ $companyEmail }}
                            @endif
                            @if (! empty($companyWebsite))
                                <br>Website: {{ $companyWebsite }}
                            @endif
                        </address>
                    </div>
                    <div class="inv-office__logo" style="flex: 0 0 auto;">
                        @if ($logoSrc)
                            <img src="{{ $logoSrc }}" alt="Logo {{ $companyName }}">
                        @endif
                    </div>
                </div>

        @php
            // Hitung total berdasarkan harga publik item dari semua produk dalam order
            $totalPublicPrice = 0;
            $totalVendorPrice = 0;
            $totalAdditionAmount = 0;
            $totalAdditionVendorAmount = 0;
            $totalDiscountAmount = 0;

            // Loop melalui semua item order untuk menghitung total
            foreach ($order->items as $orderItem) {
                $product = $orderItem->product;
                if ($product) {
                    // Hitung berdasarkan quantity dari order item
                    $quantity = $orderItem->quantity ?? 1;

                    // Total harga publish dan vendor dari product items
                    $productPublicPrice = ($product->items ?? collect())->sum(function ($item) {
                        return ($item->harga_publish ?? 0) * ($item->quantity ?? 1);
                    });

                    $productVendorPrice = ($product->items ?? collect())->sum(function ($item) {
                        return ($item->harga_vendor ?? 0) * ($item->quantity ?? 1);
                    });

                    // Akumulasi berdasarkan quantity order
                    $totalPublicPrice += $productPublicPrice * $quantity;
                    $totalVendorPrice += $productVendorPrice * $quantity;

                    // Total penambahan dari product
                    $productAdditionPublish = ($product->penambahanHarga ?? collect())->sum('harga_publish');
                    $productAdditionVendor = ($product->penambahanHarga ?? collect())->sum('harga_vendor');

                    $totalAdditionAmount += $productAdditionPublish * $quantity;
                    $totalAdditionVendorAmount += $productAdditionVendor * $quantity;

                    // Total pengurangan dari product
                    $productDiscount = ($product->pengurangans ?? collect())->sum('amount');
                    $totalDiscountAmount += $productDiscount * $quantity;
                }
            }

            // Harga dasar paket adalah total harga publik
            $basePackagePrice = $totalPublicPrice;

            // Hitung harga final setelah diskon dan penambahan
            $finalPriceAfterDiscounts = $basePackagePrice - $totalDiscountAmount + $totalAdditionAmount;
            $finalVendorPriceAfterDiscounts = $totalVendorPrice - $totalDiscountAmount + $totalAdditionVendorAmount;

            // Hitung Profit & Loss dari perhitungan detail
            $calculatedProfitLoss = $finalPriceAfterDiscounts - $finalVendorPriceAfterDiscounts;
        @endphp

        <!-- Billing Information -->
        <div class="billing-info text-sm sm:text-base">
            <div>
                <h2 class="pm-section-title">Billed To</h2>
                <table class="w-full text-gray-600">
                    <tr>
                        <td class="align-top whitespace-nowrap pr-2 pm-muted">Event</td>
                        <td class="align-top px-2 pm-muted">:</td>
                        <td class="align-top pm-ink">{{ $order->prospect->name_event ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="align-top whitespace-nowrap pr-2 pm-muted">Nama</td>
                        <td class="align-top px-2 pm-muted">:</td>
                        <td class="align-top pm-ink">CPP_{{ $order->prospect->name_cpp }} & CPW_{{ $order->prospect->name_cpw }}</td>
                    </tr>
                    <tr>
                        <td class="align-top whitespace-nowrap pr-2 pm-muted">Alamat</td>
                        <td class="align-top px-2 pm-muted">:</td>
                        <td class="align-top pm-ink">{{ ucwords(strtolower($order->prospect->address ?? 'N/A')) }}</td>
                    </tr>
                    <tr>
                        <td class="align-top whitespace-nowrap pr-2 pm-muted">No Tlp</td>
                        <td class="align-top px-2 pm-muted">:</td>
                        <td class="align-top pm-ink">+62{{ $order->prospect->phone ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="align-top whitespace-nowrap pr-2 pm-muted">Venue</td>
                        <td class="align-top px-2 pm-muted">:</td>
                        <td class="align-top pm-ink">{{ $order->prospect->venue ?? 'N/A' }} / {{ $order->pax ?? 'N/A' }} Pax</td>
                    </tr>
                    <tr>
                        <td class="align-top whitespace-nowrap pr-2 pm-muted">Account Manager</td>
                        <td class="align-top px-2 pm-muted">:</td>
                        <td class="align-top pm-ink">{{ $order->user->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="align-top whitespace-nowrap pr-2 pm-muted">Event Manager</td>
                        <td class="align-top px-2 pm-muted">:</td>
                        <td class="align-top pm-ink">{{ $order->employee->name ?? 'N/A' }}</td>
                    </tr>
                </table>
            </div>
            <div>
                <h2 class="pm-section-title">Invoice Information</h2>
                <table class="w-full text-gray-600">
                    <tr>
                        <td class="align-top whitespace-nowrap pr-2 pm-muted">Invoice Date</td>
                        <td class="align-top px-2 pm-muted">:</td>
                        <td class="align-top pm-ink">{{ now()->format('d F Y') }}</td>
                    </tr>
                    <tr>
                        <td class="align-top whitespace-nowrap pr-2 pm-muted">Due Date</td>
                        <td class="align-top px-2 pm-muted">:</td>
                        <td class="align-top pm-ink">{{ now()->addDays(30)->format('d F Y') }}</td>
                    </tr>
                    <tr>
                        <td class="align-top whitespace-nowrap pr-2 pm-muted">Status Pembayaran</td>
                        <td class="align-top px-2 pm-muted">:</td>
                        <td class="align-top status-bayar">
                            @if ($order->is_paid)
                                <span class="text-green-600 font-semibold">Paid</span>
                            @else
                                <span class="text-red-600 font-semibold">Unpaid</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="align-top whitespace-nowrap pr-2 pm-muted">Tgl Lamaran</td>
                        <td class="align-top px-2 pm-muted">:</td>
                        <td class="align-top pm-ink">{{ $order->prospect->date_lamaran ? \Carbon\Carbon::parse($order->prospect->date_lamaran)->format('d F Y') : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="align-top whitespace-nowrap pr-2 pm-muted">Tgl Akad</td>
                        <td class="align-top px-2 pm-muted">:</td>
                        <td class="align-top pm-ink">{{ $order->prospect->date_akad ? \Carbon\Carbon::parse($order->prospect->date_akad)->format('d F Y') : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="align-top whitespace-nowrap pr-2 pm-muted">Tgl Resepsi</td>
                        <td class="align-top px-2 pm-muted">:</td>
                        <td class="align-top pm-ink">{{ $order->prospect->date_resepsi ? \Carbon\Carbon::parse($order->prospect->date_resepsi)->format('d F Y') : '-' }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Rincian Perhitungan Pada Product -->
        <div class="mt-8 pt-10 mb-10">
            <h3 class="section-header">
                <svg xmlns="http://www.w3.org/2000/svg" class="section-header-icon" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="section-header-content">
                    <span class="section-header-title">Rincian Perhitungan Pada Product</span>
                    <p class="section-description">Menampilkan rincian item yang menjadi faktor pengurang dari total
                        harga paket produk.</p>
                </div>
            </h3>
            <div class="overflow-x-auto">
                <table class="item-pengurangan-table w-full text-sm sm:text-base">
                    <thead>
                        <tr>
                            <th colspan="2"
                                class="bg-gray-100 dark:bg-gray-700 text-left px-4 py-2 font-semibold text-gray-700 dark:text-white">
                                Price Calculation Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td
                                class="px-4 py-2 border-b border-gray-200 dark:border-gray-600 text-gray-900 dark:text-white">
                                Total Publish Price</td>
                            <td
                                class="text-right px-4 py-2 border-b border-gray-200 dark:border-gray-600 text-gray-900 dark:text-white">
                                Rp
                                {{ number_format($basePackagePrice, 0, ',', '.') }}
                            </td>
                        </tr>
                        <tr>
                            <td
                                class="px-4 py-2 border-b border-gray-200 dark:border-gray-600 text-gray-900 dark:text-white">
                                Total Vendor Price</td>
                            <td
                                class="text-right px-4 py-2 border-b border-gray-200 dark:border-gray-600 text-gray-900 dark:text-white">
                                Rp
                                {{ number_format($totalVendorPrice, 0, ',', '.') }}
                            </td>
                        </tr>

                        @if ($totalAdditionAmount > 0)
                            <tr>
                                <td
                                    class="px-4 py-2 border-b border-gray-200 dark:border-gray-600 text-gray-900 dark:text-white">
                                    Total Addition Publish (Penambahan)</td>
                                <td
                                    class="text-right px-4 py-2 border-b border-gray-200 dark:border-gray-600 text-green-600">
                                    + Rp
                                    {{ number_format($totalAdditionAmount, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endif

                        @if ($totalAdditionVendorAmount > 0)
                            <tr>
                                <td
                                    class="px-4 py-2 border-b border-gray-200 dark:border-gray-600 text-gray-900 dark:text-white">
                                    Total Addition Vendor (Penambahan)</td>
                                <td
                                    class="text-right px-4 py-2 border-b border-gray-200 dark:border-gray-600 text-green-600">
                                    + Rp
                                    {{ number_format($totalAdditionVendorAmount, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endif

                        @if ($totalDiscountAmount > 0)
                            <tr>
                                <td
                                    class="px-4 py-2 border-b border-gray-200 dark:border-gray-600 text-gray-900 dark:text-white">
                                    Total Reduction (Pengurangan)</td>
                                <td
                                    class="text-right px-4 py-2 border-b border-gray-200 dark:border-gray-600 text-red-600">
                                    - Rp
                                    {{ number_format($totalDiscountAmount, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endif

                        <tr>
                            <td
                                class="font-semibold px-4 py-2 border-b border-gray-200 dark:border-gray-600 text-gray-900 dark:text-white">
                                Total Paket Publish</td>
                            <td
                                class="text-right font-semibold px-4 py-2 border-b border-gray-200 dark:border-gray-600 text-gray-900 dark:text-white">
                                Rp
                                {{ number_format($finalPriceAfterDiscounts, 0, ',', '.') }}
                            </td>
                        </tr>
                        <tr>
                            <td
                                class="font-semibold px-4 py-2 border-b border-gray-200 dark:border-gray-600 text-gray-900 dark:text-white">
                                Total Paket Vendor</td>
                            <td
                                class="text-right font-semibold px-4 py-2 border-b border-gray-200 dark:border-gray-600 text-gray-900 dark:text-white">
                                Rp
                                {{ number_format($finalVendorPriceAfterDiscounts, 0, ',', '.') }}
                            </td>
                        </tr>
                        <tr>
                            <td
                                class="font-semibold px-4 py-2 border-b border-gray-200 dark:border-gray-600 text-gray-900 dark:text-white">
                                Calculated Profit & Loss</td>
                            <td
                                class="text-right font-semibold px-4 py-2 border-b border-gray-200 dark:border-gray-600 {{ $calculatedProfitLoss < 25000000 ? 'text-red-600' : 'text-green-600' }}">
                                <strong>Rp {{ number_format($calculatedProfitLoss, 0, ',', '.') }}</strong>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Rincian Perhitungan Realisasi -->
        <div class="mt-8 pt-10 mb-10">
            <h3 class="section-header">
                <svg xmlns="http://www.w3.org/2000/svg" class="section-header-icon" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="section-header-content">
                    <span class="section-header-title">Rincian Perhitungan Realisasi</span>
                    <p class="section-description">Menampilkan rincian perhitungan yang telah di realisasikan.</p>
                </div>
            </h3>
            <div class="overflow-x-auto">
                <table class="item-pengurangan-table w-full text-sm sm:text-base">
                    <thead class="bg-gray-50">
                        <tr>
                            <th colspan="2"
                                class="bg-gray-100 dark:bg-gray-700 text-left px-4 py-2 font-semibold text-gray-700 dark:text-white">
                                Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td
                                class="px-4 py-2 border-b border-gray-200 dark:border-gray-600 text-gray-900 dark:text-white">
                                Total Paket Awal</td>
                            <td
                                class="text-right px-4 py-2 border-b border-gray-200 dark:border-gray-600 text-gray-900 dark:text-white">
                                Rp
                                {{ number_format($basePackagePrice, 0, ',', '.') }}
                            </td>
                        </tr>

                        @if ($order->promo > 0)
                            <tr>
                                <td
                                    class="px-4 py-2 border-b border-gray-200 dark:border-gray-600 text-gray-900 dark:text-white">
                                    Diskon</td>
                                <td
                                    class="text-right px-4 py-2 border-b border-gray-200 dark:border-gray-600 text-gray-900 dark:text-white">
                                    - Rp
                                    {{ number_format($order->promo, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endif

                        @if ($totalAdditionAmount > 0)
                            <tr>
                                <td
                                    class="px-4 py-2 border-b border-gray-200 dark:border-gray-600 text-gray-900 dark:text-white">
                                    Penambahan</td>
                                <td
                                    class="text-right px-4 py-2 border-b border-gray-200 dark:border-gray-600 text-green-700 font-semibold">
                                    + Rp
                                    {{ number_format($totalAdditionAmount, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endif

                        @if ($order->pengurangan > 0)
                            <tr>
                                <td
                                    class="px-4 py-2 border-b border-gray-200 dark:border-gray-600 text-gray-900 dark:text-white">
                                    Pengurangan</td>
                                <td
                                    class="text-right px-4 py-2 border-b border-gray-200 dark:border-gray-600 text-gray-900 dark:text-white">
                                    - Rp
                                    {{ number_format($order->pengurangan, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endif

                        <tr>
                            <td
                                class="font-semibold px-4 py-2 border-b border-gray-200 dark:border-gray-600 text-gray-900 dark:text-white">
                                Grand Total</td>
                            <td
                                class="text-right font-semibold px-4 py-2 border-b border-gray-200 dark:border-gray-600 text-gray-900 dark:text-white">
                                Rp
                                {{ number_format($order->grand_total, 0, ',', '.') }}
                            </td>
                        </tr>
                        <tr>
                            <td
                                class="px-4 py-2 border-b border-gray-200 dark:border-gray-600 text-gray-900 dark:text-white">
                                Sudah Dibayar</td>
                            <td
                                class="text-right px-4 py-2 border-b border-gray-200 dark:border-gray-600 text-gray-900 dark:text-white">
                                Rp
                                {{ number_format($order->bayar, 0, ',', '.') }}
                            </td>
                        </tr>
                        <tr>
                            <td
                                class="px-4 py-2 border-b border-gray-200 dark:border-gray-600 text-gray-900 dark:text-white">
                                Total Pembayaran Vendor</td>
                            <td
                                class="text-right px-4 py-2 border-b border-gray-200 dark:border-gray-600 text-gray-900 dark:text-white">
                                Rp
                                {{ number_format($totalVendor, 0, ',', '.') }}
                            </td>
                        </tr>
                        <tr class="total">
                            <td
                                class="font-semibold px-4 py-2 border-b border-gray-200 dark:border-gray-600 text-gray-900 dark:text-white">
                                Sisa Tagihan (Balance
                                Due)
                            </td>
                            <td
                                class="text-right font-semibold px-4 py-2 border-b border-gray-200 dark:border-gray-600 text-gray-900 dark:text-white">
                                <strong>Rp
                                    {{ number_format($order->sisa, 0, ',', '.') }}</strong>
                            </td>
                        </tr>
                    </tbody>
                </table>

                @php $profitLoss = $order->laba_kotor ?? 0; @endphp
                <div class="profit-loss-card {{ $profitLoss >= 0 ? 'is-profit' : 'is-loss' }}">
                    <div class="profit-loss-card-content">
                        <div class="profit-loss-card-details">
                            <p class="profit-loss-card-title">Laba / Rugi Kotor</p>
                            <p class="profit-loss-card-description">Grand Total - Total Pembayaran Vendor</p>
                            @php
                                $selisihProfitLoss = $profitLoss - $calculatedProfitLoss;
                            @endphp
                            <p class="profit-loss-card-description">Selisih dengan Calculated Profit & Loss:
                                <span
                                    class="{{ $selisihProfitLoss >= 0 ? 'text-green-600' : 'text-red-600' }} font-semibold">
                                    {{ $selisihProfitLoss >= 0 ? '+' : '' }}Rp
                                    {{ number_format($selisihProfitLoss, 0, ',', '.') }}
                                </span>
                            </p>
                        </div>
                        <p class="profit-loss-card-amount">
                            Rp {{ number_format($profitLoss, 0, ',', '.') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detail Pengurangan per Produk dalam Order -->
        @php
            // For better practice, this logic should be moved to an accessor in the Order model,
            // e.g., public function getAllProductPengurangansAttribute()
            $allProductPengurangans = collect();
            if ($order->items && $order->items->count() > 0) {
                foreach ($order->items as $orderItem) {
                    if ($orderItem->product && $orderItem->product->pengurangans->count() > 0) {
                        foreach ($orderItem->product->pengurangans as $pengurangan) {
                            // Menambahkan nama produk ke objek pengurangan untuk referensi
                            $pengurangan->product_name = $orderItem->product->name;
                            $allProductPengurangans->push($pengurangan);
                        }
                    }
                }
            }
        @endphp

        <div class="mt-8 pt-10 mb-10">
            <h3 class="section-header">
                <svg xmlns="http://www.w3.org/2000/svg" class="section-header-icon" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="section-header-content">
                    <span class="section-header-title">Rincian Item Pengurangan Produk</span>
                    <p class="section-description">Menampilkan rincian item yang menjadi faktor pengurang dari total
                        harga paket produk.</p>
                </div>
            </h3>
            <div class="overflow-x-auto">
                <table class="item-pengurangan-table w-full text-sm sm:text-base">
                    <thead class="bg-gray-50">
                        <tr>
                            <th
                                class="bg-gray-100 dark:bg-gray-700 px-4 py-2 text-center w-10 text-gray-700 dark:text-white font-medium">
                                No</th>
                            <th
                                class="bg-gray-100 dark:bg-gray-700 px-4 py-2 text-left text-gray-700 dark:text-white font-medium">
                                Deskripsi
                                Pengurangan
                            </th>
                            <th
                                class="bg-gray-100 dark:bg-gray-700 px-4 py-2 text-right text-gray-700 dark:text-white font-medium w-2/5">
                                Nilai</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($allProductPengurangans as $index => $itemPengurangan)
                            <tr>
                                <td
                                    class="text-center px-4 py-2 border-b border-gray-200 dark:border-gray-600 text-gray-900 dark:text-white">
                                    {{ $index + 1 }}</td>
                                <td
                                    class="px-4 py-2 border-b border-gray-200 dark:border-gray-600 text-gray-900 dark:text-white">
                                    <div>
                                        {{ ucwords(strtolower($itemPengurangan->description ?? 'N/A')) }}
                                    </div>
                                    @if ($itemPengurangan->notes)
                                        <div class="ml-7 text-gray-600 dark:text-white">
                                            {!! strip_tags($itemPengurangan->notes, '<li><strong><em><ul><br><span><div>') !!}
                                        </div>
                                    @endif
                                </td>
                                <td
                                    class="text-right px-4 py-2 border-b border-gray-200 dark:border-gray-600 text-gray-900 dark:text-white">
                                    Rp
                                    {{ number_format($itemPengurangan->amount ?? 0, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3"
                                    class="text-center px-4 py-3 border-b border-gray-200 dark:border-gray-600 text-gray-500 dark:text-gray-100 italic">
                                    Tidak ada item pengurangan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Payment Progress Bar -->
        <div class="progress-bar-container">
            <div class="progress-bar-header">
                <span class="progress-bar-label">Progress Pembayaran</span>
                <span class="progress-bar-percentage">{{ number_format($paymentProgress, 1) }}%</span>
            </div>
            <div class="progress-bar-track">
                <div class="progress-bar-fill" style="width: {{ $paymentProgress }}%"></div>
            </div>
        </div>

        <!-- Payment History -->
        <div class="mt-8">
            <h3 class="section-header">
                <svg xmlns="http://www.w3.org/2000/svg" class="section-header-icon" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                </svg>
                <div class="section-header-content">
                    <span class="section-header-title">Payment History</span>
                    <p class="section-description">Riwayat semua pembayaran yang telah diterima dari klien untuk
                        invoice ini.</p>
                </div>
            </h3>
            <div class="overflow-x-auto">
                <table class="payment-history-table w-full text-sm sm:text-base">
                    <thead class="bg-gray-50">
                        <tr>
                            <th
                                class="bg-gray-100 dark:bg-gray-700 px-4 py-2 text-left text-gray-700 dark:text-white font-medium">
                                Date</th>
                            <th
                                class="bg-gray-100 dark:bg-gray-700 px-4 py-2 text-right text-gray-700 dark:text-white font-medium">
                                Amount</th>
                            <th
                                class="bg-gray-100 dark:bg-gray-700 px-4 py-2 text-left text-gray-700 dark:text-white font-medium">
                                Payment Method
                            </th>
                            <th
                                class="bg-gray-100 dark:bg-gray-700 px-4 py-2 text-left text-gray-700 dark:text-white font-medium">
                                Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($order->dataPembayaran as $payment)
                            <tr>
                                <td
                                    class="px-4 py-2 border-b border-gray-200 dark:border-gray-600 text-gray-900 dark:text-white">
                                    {{ \Carbon\Carbon::parse($payment->tgl_bayar)->format('d F Y') }}
                                </td>
                                <td
                                    class="text-right px-4 py-2 border-b border-gray-200 dark:border-gray-600 text-gray-900 dark:text-white">
                                    Rp
                                    {{ number_format($payment->nominal, 0, ',', '.') }}
                                </td>
                                <td
                                    class="px-4 py-2 border-b border-gray-200 dark:border-gray-600 text-gray-900 dark:text-white">
                                    @if ($payment->paymentMethod)
                                        <div>
                                            <span
                                                class="font-medium text-gray-900 dark:text-white">{{ $payment->paymentMethod->name }}</span>
                                            @if ($payment->paymentMethod->no_rekening)
                                                <br>
                                                <span
                                                    class="text-sm text-gray-600 dark:text-white">{{ $payment->paymentMethod->no_rekening }}</span>
                                            @endif
                                        </div>
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td
                                    class="px-4 py-2 border-b border-gray-200 dark:border-gray-600 text-gray-900 dark:text-white">
                                    {{ $payment->keterangan }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4"
                                    class="text-center px-4 py-3 text-gray-500 dark:text-gray-100 italic">
                                    No payment history available.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pembayaran Vendor -->
        <div class="mt-8">
            <h3 class="section-header">
                <svg xmlns="http://www.w3.org/2000/svg" class="section-header-icon" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <div class="section-header-content">
                    <span class="section-header-title">Pembayaran Vendor</span>
                    <p class="section-description">Rincian semua pengeluaran yang telah dibayarkan kepada vendor
                        terkait proyek ini.</p>
                </div>
            </h3>
            <div class="overflow-x-auto">
                <table class="vendor-payment-table w-full text-sm sm:text-base">
                    <thead class="bg-gray-50">
                        <tr>
                            <th
                                class="bg-gray-100 dark:bg-gray-700 px-4 py-2 text-left text-gray-700 dark:text-white font-medium">
                                Tgl</th>
                            <th
                                class="bg-gray-100 dark:bg-gray-700 px-4 py-2 text-left text-gray-700 dark:text-white font-medium">
                                Vendor</th>
                            <th
                                class="bg-gray-100 dark:bg-gray-700 px-4 py-2 text-left text-gray-700 dark:text-white font-medium">
                                Keterangan</th>
                            <th
                                class="bg-gray-100 dark:bg-gray-700 px-4 py-2 text-left text-gray-700 dark:text-white font-medium">
                                No ND</th>
                            <th
                                class="bg-gray-100 dark:bg-gray-700 px-4 py-2 text-right text-gray-700 dark:text-white font-medium">
                                Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $visibleLimit = 5; @endphp
                        @forelse($allExpenses as $expense)
                            <tr class="vendor-expense-row @if ($loop->iteration > $visibleLimit) hidden @endif">
                                <td
                                    class="px-4 py-2 border-b border-gray-200 dark:border-gray-600 text-gray-900 dark:text-white">
                                    {{ $expense->date_expense ? \Carbon\Carbon::parse($expense->date_expense)->format('d M Y') : '-' }}
                                </td>
                                <td
                                    class="px-4 py-2 border-b border-gray-200 dark:border-gray-600 text-gray-900 dark:text-white">
                                    {{ $expense->vendor->name ?? 'N/A' }}
                                </td>
                                <td
                                    class="px-4 py-2 border-b border-gray-200 dark:border-gray-600 text-gray-900 dark:text-white">
                                    {{ ucwords(strtolower($expense->note ?? 'N/A')) }}
                                </td>
                                <td
                                    class="px-4 py-2 border-b border-gray-200 dark:border-gray-600 text-gray-900 dark:text-white">
                                    {{ $expense->no_nd ? '' . $expense->no_nd : '-' }}
                                </td>
                                <td
                                    class="text-right px-4 py-2 border-b border-gray-200 dark:border-gray-600 text-gray-900 dark:text-white">
                                    Rp
                                    {{ number_format($expense->amount ?? 0, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4"
                                    class="text-center px-4 py-3 text-gray-500 dark:text-gray-100 italic">
                                    Tidak ada data pembayaran vendor.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{-- Tombol "Show More" --}}
            @if ($allExpenses->count() > $visibleLimit)
                <div class="flex justify-center">
                    <button id="toggle-vendor-expenses" class="show-more-button">
                        Tampilkan {{ $allExpenses->count() - $visibleLimit }} Lainnya
                    </button>
                </div>
            @endif
        </div>
            </div>{{-- /.pm-panel --}}
        </div>{{-- /.pm-tabs-shell --}}
    </div>{{-- /.pm-wf --}}

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleButton = document.getElementById('toggle-vendor-expenses');
            const rows = document.querySelectorAll('tr.vendor-expense-row');
            const visibleLimit = {{ $visibleLimit }};

            if (toggleButton) {
                toggleButton.addEventListener('click', function() {
                    let isShowingAll = this.dataset.showingAll === 'true';

                    rows.forEach((row, index) => {
                        if (index >= visibleLimit) {
                            row.classList.toggle('hidden');
                        }
                    });

                    // Update button text and state
                    isShowingAll = !isShowingAll;
                    this.dataset.showingAll = isShowingAll;
                    this.textContent = isShowingAll ? 'Tampilkan Lebih Sedikit' :
                        'Tampilkan {{ $allExpenses->count() - $visibleLimit }} Lainnya';
                });
            }
        });
    </script>
</x-filament-panels::page>
