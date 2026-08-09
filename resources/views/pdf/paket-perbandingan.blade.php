<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Perbedaan Paket WOFINS</title>
    <style>
        @page { margin: 28px 32px; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #1a2b44;
            line-height: 1.45;
        }
        h1 {
            font-size: 18px;
            margin: 0 0 4px;
            color: #0f2744;
        }
        h2 {
            font-size: 12px;
            margin: 18px 0 8px;
            color: #0f2744;
            border-bottom: 1.5px solid #c9a227;
            padding-bottom: 4px;
        }
        .meta {
            color: #5b6b7c;
            font-size: 9px;
            margin-bottom: 14px;
        }
        .hero {
            background: #0f2744;
            color: #fff;
            padding: 14px 16px;
            border-radius: 8px;
            margin-bottom: 14px;
        }
        .hero h1 { color: #fff; }
        .hero .gold { color: #e8d48b; font-size: 9px; letter-spacing: 0.12em; text-transform: uppercase; font-weight: bold; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        th, td {
            border: 1px solid #d7dde5;
            padding: 5px 6px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background: #0f2744;
            color: #fff;
            font-weight: bold;
            font-size: 9px;
        }
        th.pro { background: #9a7a12; }
        td.num { text-align: right; white-space: nowrap; }
        td.center { text-align: center; }
        tr:nth-child(even) td { background: #f7f4ec; }
        .note {
            background: #f7f4ec;
            border-left: 3px solid #c9a227;
            padding: 8px 10px;
            margin: 10px 0;
            font-size: 9px;
            color: #3d4a5c;
        }
        .cards td {
            width: 33%;
            background: #fff !important;
            vertical-align: top;
        }
        .card-title {
            font-size: 12px;
            font-weight: bold;
            color: #0f2744;
            margin-bottom: 2px;
        }
        .card-price {
            font-size: 14px;
            font-weight: bold;
            color: #9a7a12;
        }
        .card-desc { color: #5b6b7c; font-size: 9px; margin: 4px 0 8px; }
        ul { margin: 0; padding-left: 14px; }
        li { margin-bottom: 2px; }
        .footer {
            margin-top: 16px;
            font-size: 8px;
            color: #7a8794;
            border-top: 1px solid #d7dde5;
            padding-top: 6px;
        }
        .yes { color: #059669; font-weight: bold; }
        .no { color: #94a3b8; }
    </style>
</head>
<body>
@php
    $fmtMoney = static function (int $amount): string {
        return 'Rp '.number_format($amount, 0, ',', '.');
    };
    $starter = collect($plans)->firstWhere('key', 'starter') ?? [];
    $pro = collect($plans)->firstWhere('key', 'professional') ?? [];
    $business = collect($plans)->firstWhere('key', 'business') ?? [];
@endphp

<div class="hero">
    <div class="gold">WOFINS · Wedding Organizer Financial Information System</div>
    <h1>Perbedaan Paket Berlangganan</h1>
    <div style="font-size:9px;color:rgba(255,255,255,0.75);margin-top:4px;">
        1 WO = 1 Company = beberapa pengguna · Paket di companies.subscription_plan
    </div>
</div>

<p class="meta">Dibuat: {{ $generatedAt }} · Sumber: PricingPlans</p>

<h2>1. Ringkasan harga</h2>
<table class="cards">
    <tr>
        @foreach ([$starter, $pro, $business] as $plan)
            <td>
                <div class="card-title">{{ $plan['name'] ?? '-' }}</div>
                <div class="card-price">{{ $fmtMoney((int) ($plan['price_monthly'] ?? 0)) }}<span style="font-size:9px;font-weight:normal;color:#5b6b7c;"> / bln</span></div>
                <div class="card-desc">
                    Tahunan: {{ $fmtMoney((int) ($plan['price_annual'] ?? 0)) }} (hemat 1 bulan)<br>
                    {{ $plan['desc'] ?? '' }}
                </div>
            </td>
        @endforeach
    </tr>
</table>

<h2>2. Kuota resource</h2>
<table>
    <thead>
        <tr>
            <th>Resource</th>
            <th>Starter</th>
            <th class="pro">Professional</th>
            <th>Business</th>
        </tr>
    </thead>
    <tbody>
        @foreach ([
            ['Pengguna (seat)', 'seat_limit'],
            ['Vendor', 'vendor_limit'],
            ['Produk', 'product_limit'],
            ['Proyek wedding', 'order_limit'],
            ['Prospek', 'prospect_limit'],
            ['Simulasi', 'simulasi_limit'],
            ['Rekening bank/kas', 'payment_method_limit'],
            ['Aset tetap', 'fixed_asset_limit'],
            ['Piutang', 'piutang_limit'],
            ['Pembayaran piutang', 'pembayaran_piutang_limit'],
            ['Pendapatan wedding', 'data_pembayaran_limit'],
            ['Pengeluaran wedding', 'expense_limit'],
            ['Pengeluaran operasional', 'expense_ops_limit'],
            ['Pendapatan lain', 'pendapatan_lain_limit'],
            ['Pengeluaran lain', 'pengeluaran_lain_limit'],
        ] as [$label, $key])
            <tr>
                <td>{{ $label }}</td>
                <td class="num">{{ number_format((int) ($starter[$key] ?? 0), 0, ',', '.') }}</td>
                <td class="num">{{ number_format((int) ($pro[$key] ?? 0), 0, ',', '.') }}</td>
                <td class="num">{{ number_format((int) ($business[$key] ?? 0), 0, ',', '.') }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<div class="note">
    <strong>Tanpa kuota paket:</strong>
    Kategori dikelola admin platform (create/update/delete hanya super admin).
    Crew freelance tersedia di semua paket — bukan akun user, bisa diisi lewat link undangan publik.
</div>

<h2>3. Perbandingan fitur</h2>
<table>
    <thead>
        <tr>
            <th>Fitur</th>
            <th>Starter</th>
            <th class="pro">Professional</th>
            <th>Business</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($compareRows as $row)
            <tr>
                @foreach ($row as $i => $cell)
                    <td class="{{ $i === 0 ? '' : 'center' }}">
                        @if ($i === 0)
                            {{ $cell }}
                        @elseif ($cell === true)
                            <span class="yes">Ya</span>
                        @elseif ($cell === false)
                            <span class="no">—</span>
                        @else
                            {{ $cell }}
                        @endif
                    </td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>

<h2>4. Highlight tiap paket</h2>
<table class="cards">
    <tr>
        <td>
            <div class="card-title">Starter</div>
            <ul>
                @foreach (($starter['features'] ?? []) as $f)
                    <li>{{ $f['label'] }}</li>
                @endforeach
            </ul>
        </td>
        <td>
            <div class="card-title">Professional</div>
            <ul>
                @foreach (($pro['features'] ?? []) as $f)
                    <li>{{ $f['label'] }}</li>
                @endforeach
            </ul>
        </td>
        <td>
            <div class="card-title">Business</div>
            <ul>
                @foreach (($business['features'] ?? []) as $f)
                    <li>{{ $f['label'] }}</li>
                @endforeach
            </ul>
        </td>
    </tr>
</table>

<div class="footer">
    WOFINS / Makna Kreatif · Dokumen internal &amp; referensi penjualan · {{ $generatedAt }}
</div>
</body>
</html>
