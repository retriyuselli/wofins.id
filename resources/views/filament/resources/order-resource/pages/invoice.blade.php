@php
    $companyName = $companyName ?? config('app.name');
    $companyAddress = $companyAddress ?? null;
    $companyPhone = $companyPhone ?? null;
    $companyEmail = $companyEmail ?? null;
    $companyWebsite = $companyWebsite ?? null;
    $companyLogoUrl = $companyLogoUrl ?? null;
    $logoSrc = $companyLogoUrl ?: '';
@endphp

<x-filament-panels::page>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assetssimulasi/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assetssimulasi/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/invoice/invoice.css') }}">

    <style>
        .wofins-invoice-page,
        .wofins-invoice-page * {
            font-family: 'Noto Sans', sans-serif !important;
        }

        .wofins-invoice-page .invoice-container-wrap {
            background: transparent !important;
            min-height: auto !important;
            padding: 0 !important;
            display: block !important;
        }

        .wofins-invoice-page .invoice-container {
            background: #fff !important;
            border: 1px solid #e5e7eb !important;
            border-radius: 12px !important;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04) !important;
            margin: 0 auto !important;
            max-width: 1100px !important;
            padding: 24px !important;
            width: 100% !important;
        }

        .wofins-invoice-page .themeholy-header address {
            margin: 0;
            font-style: normal;
            line-height: 1.45;
            color: #1f2937;
            font-size: 13px;
        }

        .wofins-invoice-page .invoice-actions a {
            text-decoration: none;
        }

        .wofins-invoice-page .section-header-title,
        .wofins-invoice-page h1,
        .wofins-invoice-page h2,
        .wofins-invoice-page h3 {
            color: #0b1f3a;
        }
    </style>

    <div class="wofins-invoice-page">
        <div class="invoice-container-wrap">
            <div class="invoice-container themeholy-invoice invoice_style2">
                <header class="themeholy-header header-layout1">
                    <div class="d-flex align-items-start justify-content-between"
                        style="border-bottom: 1px solid #000; padding-bottom: 14px; margin-bottom: 20px; gap: 12px;">
                        <div style="text-align: left; min-width: 0; flex: 1 1 auto;">
                            <b>Office Information :</b>
                            <address style="white-space: normal; overflow-wrap: anywhere; word-break: break-word;">
                                {{ $companyName }}<br>
                                {{ $companyAddress ?: 'Alamat belum diatur' }}
                                |
                                Phone: {{ $companyPhone ?: '-' }}
                                @if (! empty($companyEmail))
                                    <br>Email: {{ $companyEmail }}
                                @endif
                                @if (! empty($companyWebsite))
                                    <br>Website: {{ $companyWebsite }}
                                @endif
                            </address>
                        </div>
                        <div class="header-logo"
                            style="max-height: 64px; text-align: right; flex: 0 0 auto; margin-left: auto;">
                            @if ($logoSrc)
                                <img src="{{ $logoSrc }}" alt="Logo {{ $companyName }}"
                                    style="display: block; max-height: 64px; width: auto; max-width: 160px; margin-left: auto; object-fit: contain;">
                            @endif
                        </div>
                    </div>
                </header>

                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                    <div>
                        <h1 class="fw-bold mb-1" style="font-size: 1.15rem;">
                            DETAILS #{{ $order->number ?? $order->id }}
                        </h1>
                        <p class="mb-0 text-muted" style="font-size: 0.9rem;">
                            Date: {{ $order->created_at?->format('d M Y') }}
                        </p>
                    </div>

                    <div class="invoice-actions d-flex flex-wrap gap-2 justify-content-end">
                        @php
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
                        @endphp

                        @if ($phone)
                            <a href="{{ $whatsappUrl }}" target="_blank"
                                class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-sm text-white hover:bg-green-500">
                                WhatsApp
                            </a>
                        @endif

                        <a href="{{ route('invoice.download', ['order' => $order]) }}" target="_blank"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-sm text-white hover:bg-primary-500">
                            Download Invoice
                        </a>
                    </div>
                </div>

        @php
            $grandTotal = $order->grand_total ?? 0;
            $totalPaid = $order->bayar ?? 0;
            $paymentProgress = $grandTotal > 0 ? ($totalPaid / $grandTotal) * 100 : 0;
            $paymentProgress = min($paymentProgress, 100);

            // Hitung total berdasarkan jumlah harga publik item dari semua produk dalam order
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
            {{-- <div class="mt-6 grid grid-cols-2 gap-4 text-sm"> --}}
            <div>
                <h2 class="text-gray-700 dark:text-white font-bold mb-2">Billed To :</h2>
                <table class="w-full text-gray-600 dark:text-white">
                    <tr>
                        <td class="align-top whitespace-nowrap pr-2">Event</td>
                        <td class="align-top px-2">:</td>
                        <td class="align-top">{{ $order->prospect->name_event ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="align-top whitespace-nowrap pr-2">Name Nama</td>
                        <td class="align-top px-2">:</td>
                        <td class="align-top">CPP_{{ $order->prospect->name_cpp }} & CPW_{{ $order->prospect->name_cpw }}</td>
                    </tr>
                    <tr>
                        <td class="align-top whitespace-nowrap pr-2">Alamat</td>
                        <td class="align-top px-2">:</td>
                        <td class="align-top">{{ ucwords(strtolower($order->prospect->address ?? 'N/A')) }}</td>
                    </tr>
                    <tr>
                        <td class="align-top whitespace-nowrap pr-2">No Tlp</td>
                        <td class="align-top px-2">:</td>
                        <td class="align-top">+62{{ $order->prospect->phone ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="align-top whitespace-nowrap pr-2">Venue</td>
                        <td class="align-top px-2">:</td>
                        <td class="align-top">{{ $order->prospect->venue ?? 'N/A' }} / {{ $order->pax ?? 'N/A' }} Pax</td>
                    </tr>
                    <tr>
                        <td class="align-top whitespace-nowrap pr-2">Account Manager</td>
                        <td class="align-top px-2">:</td>
                        <td class="align-top">{{ $order->user->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="align-top whitespace-nowrap pr-2">Event Manager</td>
                        <td class="align-top px-2">:</td>
                        <td class="align-top">{{ $order->employee->name ?? 'N/A' }}</td>
                    </tr>
                </table>
            </div>
            <div>
                <h2 class="text-sm font-semibold mb-2 text-gray-900 dark:text-white">Invoice Information :</h2>
                <table class="w-full text-gray-600 dark:text-white">
                    <tr>
                        <td class="align-top whitespace-nowrap pr-2">Invoice Date</td>
                        <td class="align-top px-2">:</td>
                        <td class="align-top">{{ now()->format('d F Y') }}</td>
                    </tr>
                    <tr>
                        <td class="align-top whitespace-nowrap pr-2">Due Date</td>
                        <td class="align-top px-2">:</td>
                        <td class="align-top">{{ now()->addDays(30)->format('d F Y') }}</td>
                    </tr>
                    <tr>
                        <td class="align-top whitespace-nowrap pr-2">Status Pembayaran</td>
                        <td class="align-top px-2">:</td>
                        <td class="align-top status-bayar">
                            @if ($order->is_paid)
                                <span class="text-green-600 font-semibold">Paid</span>
                            @else
                                <span class="text-red-600 font-semibold">Unpaid</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="align-top whitespace-nowrap pr-2">Tgl Lamaran</td>
                        <td class="align-top px-2">:</td>
                        <td class="align-top">{{ $order->prospect->date_lamaran ? \Carbon\Carbon::parse($order->prospect->date_lamaran)->format('d F Y') : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="align-top whitespace-nowrap pr-2">Tgl Akad</td>
                        <td class="align-top px-2">:</td>
                        <td class="align-top">{{ $order->prospect->date_akad ? \Carbon\Carbon::parse($order->prospect->date_akad)->format('d F Y') : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="align-top whitespace-nowrap pr-2">Tgl Resepsi</td>
                        <td class="align-top px-2">:</td>
                        <td class="align-top">{{ $order->prospect->date_resepsi ? \Carbon\Carbon::parse($order->prospect->date_resepsi)->format('d F Y') : '-' }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Separator Line -->
        <hr class="border-t-2 border-gray-200 dark:border-gray-600 py-1.5">

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
            </div>
        </div>
    </div>

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
