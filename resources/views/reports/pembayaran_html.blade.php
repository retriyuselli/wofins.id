<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Laporan Pembayaran</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --wf-navy: #0b1f3a;
            --wf-navy-deep: #071526;
            --wf-gold: #c9a227;
            --wf-gold-soft: #e8d48b;
            --wf-cream: #f7f4ee;
            --wf-ink: #1a2332;
            --wf-muted: #5c6675;
            --wf-line: #e6e2d9;
            --wf-white: #ffffff;
        }

        body {
            font-family: 'Poppins', system-ui, sans-serif;
            margin: 0;
            min-height: 100vh;
            background-color: var(--wf-cream);
            color: var(--wf-ink);
            position: relative;
            overflow-x: hidden;
        }

        .report-page {
            position: relative;
            z-index: 1;
            padding: 20px;
        }

        .report-edge-shapes {
            position: fixed;
            inset: 0;
            pointer-events: none;
            overflow: hidden;
            z-index: 0;
        }

        .report-edge-shapes .shape {
            position: absolute;
        }

        .report-edge-shapes .blob {
            border-radius: 999px;
            filter: blur(1px);
        }

        .report-edge-shapes .ring {
            border-radius: 999px;
            border: 2px solid rgba(201, 162, 39, 0.35);
            background: transparent;
        }

        .report-edge-shapes .square {
            border-radius: 0.7rem;
            border: 2px solid rgba(11, 31, 58, 0.18);
            background: rgba(201, 162, 39, 0.08);
        }

        .report-edge-shapes .dot {
            border-radius: 999px;
            background: var(--wf-gold);
        }

        .report-edge-shapes .tri {
            width: 0;
            height: 0;
            border-left: 14px solid transparent;
            border-right: 14px solid transparent;
            border-bottom: 24px solid rgba(11, 31, 58, 0.12);
        }

        /* Left edge */
        .s-l1 {
            width: 180px;
            height: 180px;
            left: -70px;
            top: 8%;
            background: radial-gradient(circle at 35% 35%, rgba(201, 162, 39, 0.35), rgba(201, 162, 39, 0.05) 70%);
            animation: wf-float 7s ease-in-out infinite;
        }

        .s-l2 {
            width: 56px;
            height: 56px;
            left: 28px;
            top: 28%;
            animation: wf-orbit 14s linear infinite;
        }

        .s-l3 {
            width: 42px;
            height: 42px;
            left: 18px;
            top: 52%;
            animation: wf-tilt-float 6.5s ease-in-out 0.8s infinite;
        }

        .s-l4 {
            width: 10px;
            height: 10px;
            left: 64px;
            top: 68%;
            opacity: 0.7;
            animation: wf-pulse 3.2s ease-in-out infinite;
        }

        .s-l5 {
            left: 36px;
            bottom: 12%;
            animation: wf-float-alt 6s ease-in-out 0.3s infinite;
        }

        /* Right edge */
        .s-r1 {
            width: 220px;
            height: 220px;
            right: -90px;
            top: 18%;
            background: radial-gradient(circle at 60% 40%, rgba(11, 31, 58, 0.18), rgba(11, 31, 58, 0.03) 72%);
            animation: wf-float-alt 8s ease-in-out infinite;
        }

        .s-r2 {
            width: 64px;
            height: 64px;
            right: 36px;
            top: 42%;
            border-color: rgba(11, 31, 58, 0.2);
            animation: wf-orbit-rev 16s linear infinite;
        }

        .s-r3 {
            width: 36px;
            height: 36px;
            right: 22px;
            top: 62%;
            background: rgba(11, 31, 58, 0.06);
            border-color: rgba(201, 162, 39, 0.4);
            animation: wf-tilt-float-alt 5.8s ease-in-out 0.5s infinite;
        }

        .s-r4 {
            width: 12px;
            height: 12px;
            right: 72px;
            bottom: 22%;
            opacity: 0.65;
            animation: wf-pulse 2.8s ease-in-out 0.4s infinite;
        }

        .s-r5 {
            width: 90px;
            height: 90px;
            right: -30px;
            bottom: 8%;
            background: radial-gradient(circle, rgba(201, 162, 39, 0.22), transparent 70%);
            animation: wf-float 7.5s ease-in-out 0.6s infinite;
        }

        /* Soft top/bottom accents */
        .s-t1 {
            width: 120px;
            height: 120px;
            left: 42%;
            top: -48px;
            background: radial-gradient(circle, rgba(201, 162, 39, 0.16), transparent 70%);
            animation: wf-pulse 5s ease-in-out infinite;
        }

        .s-b1 {
            width: 160px;
            height: 160px;
            left: 55%;
            bottom: -70px;
            background: radial-gradient(circle, rgba(11, 31, 58, 0.1), transparent 70%);
            animation: wf-float 9s ease-in-out infinite;
        }

        @keyframes wf-float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-14px); }
        }

        @keyframes wf-float-alt {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(12px); }
        }

        @keyframes wf-orbit {
            0% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-10px) rotate(180deg); }
            100% { transform: translateY(0) rotate(360deg); }
        }

        @keyframes wf-orbit-rev {
            0% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(10px) rotate(-180deg); }
            100% { transform: translateY(0) rotate(-360deg); }
        }

        @keyframes wf-tilt-float {
            0%, 100% { transform: translateY(0) rotate(18deg); }
            50% { transform: translateY(-12px) rotate(28deg); }
        }

        @keyframes wf-tilt-float-alt {
            0%, 100% { transform: translateY(0) rotate(-12deg); }
            50% { transform: translateY(10px) rotate(-4deg); }
        }

        @keyframes wf-pulse {
            0%, 100% { opacity: 0.45; transform: scale(1); }
            50% { opacity: 0.9; transform: scale(1.25); }
        }

        @media (max-width: 900px) {
            .report-edge-shapes .shape {
                opacity: 0.55;
            }

            .s-l2, .s-l3, .s-l5, .s-r2, .s-r3 {
                display: none;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .report-edge-shapes .shape {
                animation: none !important;
            }
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            background-color: var(--wf-white);
            padding: 30px;
            border-radius: 12px;
            border: 1px solid var(--wf-line);
            box-shadow: 0 12px 32px -20px rgba(11, 31, 58, 0.25);
            position: relative;
            z-index: 1;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 0.9em;
        }

        .filter-form {
            margin-bottom: 25px;
            padding: 16px 18px;
            background:
                linear-gradient(135deg, rgba(201, 162, 39, 0.08), transparent 55%),
                #f3f0e9;
            border: 1px solid var(--wf-line);
            border-radius: 10px;
            display: flex;
            gap: 15px;
            align-items: flex-end;
            flex-wrap: wrap;
        }

        .filter-form label {
            font-weight: 600;
            margin-right: 5px;
            margin-bottom: 6px;
            display: block;
            color: var(--wf-navy);
            font-size: 0.8em;
        }

        .filter-form select,
        .filter-form input,
        .filter-form button {
            padding: 8px 12px;
            border: 1px solid var(--wf-line);
            border-radius: 8px;
            font-family: 'Poppins', system-ui, sans-serif;
            font-size: 0.9em;
            color: var(--wf-ink);
            background: var(--wf-white);
        }

        .filter-form select:focus,
        .filter-form input:focus {
            outline: none;
            border-color: var(--wf-gold);
            box-shadow: 0 0 0 3px rgba(201, 162, 39, 0.2);
        }

        .filter-form button {
            background: var(--wf-navy);
            color: white;
            cursor: pointer;
            border-color: var(--wf-navy);
            font-weight: 600;
            transition: background 0.2s ease, transform 0.2s ease;
        }

        .filter-form button:hover {
            background: var(--wf-navy-deep);
            transform: translateY(-1px);
        }

        .report-title-header {
            text-align: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--wf-line);
        }

        .company-logo {
            max-height: 2.5rem;
            margin-left: auto;
            margin-right: auto;
            margin-bottom: 1rem;
        }

        h1.report-main-title {
            font-size: 1.25rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--wf-navy);
            text-align: center;
            margin-top: 0;
            margin-bottom: 0;
            border-bottom: none;
            padding-bottom: 0;
        }

        h1.report-main-title small {
            color: var(--wf-muted) !important;
        }

        .company-address {
            font-size: 0.75rem;
            color: var(--wf-muted);
            margin-top: 0.25rem;
        }

        th,
        td {
            border: 1px solid var(--wf-line);
            padding: 10px 12px;
            text-align: left;
        }

        th {
            background-color: var(--wf-navy);
            color: white;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            font-size: 0.78em;
        }

        tr:nth-child(even) {
            background-color: rgba(247, 244, 238, 0.65);
        }

        tr:hover {
            background-color: rgba(201, 162, 39, 0.1);
        }

        .no-data {
            text-align: center;
            color: var(--wf-muted);
            padding: 20px;
            font-style: italic;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 0.85em;
            color: var(--wf-muted);
        }

        .total-row td {
            font-weight: 700;
            background-color: rgba(11, 31, 58, 0.06);
            color: var(--wf-navy);
            border-top: 2px solid var(--wf-gold);
        }

        a {
            color: var(--wf-navy);
        }

        a:hover {
            color: var(--wf-gold);
        }

        .report-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 1.25rem;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 8px 14px;
            border-radius: 999px;
            border: 1px solid var(--wf-line);
            background: var(--wf-white);
            color: var(--wf-navy);
            font-family: 'Poppins', system-ui, sans-serif;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.2s ease, border-color 0.2s ease, transform 0.2s ease;
        }

        .btn-back:hover {
            background: #f3f0e9;
            border-color: var(--wf-gold);
            color: var(--wf-navy);
            transform: translateY(-1px);
        }
    </style>
</head>

<body>
    <div class="report-edge-shapes" aria-hidden="true">
        <span class="shape blob s-l1"></span>
        <span class="shape ring s-l2"></span>
        <span class="shape square s-l3"></span>
        <span class="shape dot s-l4"></span>
        <span class="shape tri s-l5"></span>

        <span class="shape blob s-r1"></span>
        <span class="shape ring s-r2"></span>
        <span class="shape square s-r3"></span>
        <span class="shape dot s-r4"></span>
        <span class="shape blob s-r5"></span>

        <span class="shape blob s-t1"></span>
        <span class="shape blob s-b1"></span>
    </div>

    <div class="report-page">
    <div class="container">
        <div class="report-toolbar">
            <a href="{{ route('filament.admin.resources.data-pembayarans.index') }}" class="btn-back" aria-label="Kembali ke daftar pembayaran">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M15 18l-6-6 6-6" />
                </svg>
                Kembali
            </a>
        </div>

        <div class="report-title-header">
            @php
                $company = null;
                if (\Illuminate\Support\Facades\Schema::hasTable('companies')) {
                    $company = \App\Models\Company::query()->first();
                }

                $logoSrc =
                    $company && $company->logo_url
                        ? \Illuminate\Support\Facades\Storage::disk('public')->url($company->logo_url)
                        : asset('images/logomki.png');
            @endphp
            @if ($logoSrc)
                <img src="{{ $logoSrc }}" alt="Nama Perusahaan Anda" class="company-logo">
            @endif

            <h1 class="report-main-title">
                Laporan Data Pembayaran
                @if (isset($selectedMonth) && isset($selectedYear) && $selectedMonth && $selectedYear)
                    <br><small style="font-size: 0.65em; font-weight: 400; color: #555;">(Periode:
                        {{ $months[$selectedMonth] }} {{ $selectedYear }})</small>
                @elseif(isset($selectedYear) && $selectedYear && (!isset($selectedMonth) || !$selectedMonth))
                    <br><small style="font-size: 0.65em; font-weight: 400; color: #555;">(Periode: Tahun
                        {{ $selectedYear }})</small>
                @else
                    <br><small style="font-size: 0.65em; font-weight: 400; color: #555; text-transform: none;">(Semua
                        Periode)</small>
                @endif
            </h1>
            @if ($company)
                <p class="company-address">
                    {{ $company->address }}
                    @if ($company->city)
                        , {{ $company->city }}
                    @endif
                    @if ($company->province)
                        , {{ $company->province }}
                    @endif
                    @if ($company->postal_code)
                        {{ $company->postal_code }}
                    @endif
                </p>
                <p class="company-address" style="margin-top:0;">
                    {{ $company->company_name }}
                    @if ($company->email)
                        | {{ $company->email }}
                    @endif
                    @if ($company->phone)
                        | {{ $company->phone }}
                    @endif
                </p>
            @else
                <p class="company-address">Jl. Sintraman Jaya I No. 2148, 20 Ilir D II, <br>
                    Kecamatan Kemuning, Kota Palembang, Sumatera Selatan 30137</p>
                <p class="company-address" style="margin-top:0;">{{ $companyName ?? config('app.name') }} | maknawedding@gmail.com |
                    +62
                    822-9796-2600</p>
            @endif
        </div>

        <form action="{{ route('data-pembayaran.html-report') }}" method="GET" class="filter-form">
            <div>
                <label for="month">Bulan:</label>
                <select name="month" id="month">
                    <option value="">-- Semua Bulan --</option>
                    @foreach ($months as $monthNum => $monthName)
                        <option value="{{ $monthNum }}"
                            {{ isset($selectedMonth) && $selectedMonth == $monthNum ? 'selected' : '' }}>
                            {{ $monthName }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="year">Tahun:</label>
                <select name="year" id="year">
                    <option value="">-- Semua Tahun --</option>
                    @foreach ($years as $year)
                        <option value="{{ $year }}"
                            {{ isset($selectedYear) && $selectedYear == $year ? 'selected' : '' }}>
                            {{ $year }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="status">Status Order:</label>
                <select name="status" id="status">
                    <option value="">-- Semua Status --</option>
                    @if (isset($orderStatuses)) {{-- Pastikan $orderStatuses tersedia --}}
                        @foreach ($orderStatuses as $status)
                            <option value="{{ $status->value }}"
                                {{ isset($selectedStatus) && $selectedStatus == $status->value ? 'selected' : '' }}>
                                {{ $status->getLabel() }}</option>
                        @endforeach
                    @endif
                </select>
            </div>
            <div>
                <label for="search_event">Nama Event:</label>
                <input type="text" name="search_event" id="search_event" value="{{ $searchEvent ?? '' }}"
                    placeholder="Cari berdasarkan nama event">
            </div>
            <button type="submit">Terapkan Filter</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama Event</th>
                    <th style="text-align: right;">Jumlah Pembayaran</th>
                    <th>Tanggal Pembayaran</th>
                    <th>Status Order</th>
                    <th>Metode Pembayaran</th>
                    <th>Bukti Pembayaran</th>
                    <th>Catatan/Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @php $totalPembayaran = 0; @endphp
                @forelse($dataPembayarans as $pembayaran)
                    <tr>
                        <td>{{ $pembayaran->id }}</td>
                        <td>{{ $pembayaran->order->name ?? 'N/A' }}</td>
                        <td style="text-align: right;"> {{ number_format($pembayaran->nominal ?? 0, 0, ',', '.') }}
                        </td>
                        <td>{{ $pembayaran->tgl_bayar ? \Carbon\Carbon::parse($pembayaran->tgl_bayar)->locale('id')->isoFormat('D MMMM YYYY') : 'N/A' }}
                        </td>
                        <td>
                            @if ($pembayaran->order && $pembayaran->order->status)
                                {{ $pembayaran->order->status->getLabel() ?? ($pembayaran->order->status->value ?? 'N/A') }}
                            @else
                                N/A
                            @endif
                        </td>
                        <td>
                            @if ($pembayaran->paymentMethod)
                                @if ($pembayaran->paymentMethod->is_cash)
                                    Cash
                                @else
                                    {{ $pembayaran->paymentMethod->bank_name ?? 'N/A' }}
                                @endif
                            @else
                                N/A
                            @endif
                        </td>
                        <td>
                            @if ($pembayaran->image)
                                @php
                                    $proofUrl = \Illuminate\Support\Facades\Storage::url($pembayaran->image);
                                @endphp
                                <a href="{{ $proofUrl }}" target="_blank" rel="noopener noreferrer">
                                    <img src="{{ $proofUrl }}" alt="Payment Proof"
                                        style="width: 40px; height: 40px; object-fit: cover; border-radius: 0;">
                                </a>
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $pembayaran->keterangan ?? '-' }}</td>
                    </tr>
                    @php $totalPembayaran += ($pembayaran->nominal ?? 0); @endphp
                @empty
                    <tr>
                        <td colspan="8" class="no-data">Tidak ada data pembayaran yang ditemukan.</td>
                    </tr>
                @endforelse
                {{-- Baris Total (Opsional) --}}
                @if ($dataPembayarans->isNotEmpty())
                    <tr class="total-row">
                        <td colspan="2" style="text-align: right;"><strong>Total Keseluruhan:</strong></td>
                        <td style="text-align: right;"><strong>
                                {{ number_format($totalPembayaran, 0, ',', '.') }}</strong></td>
                        <td colspan="5"></td>
                    </tr>
                @endif
            </tbody>
        </table>
        <div class="footer">
            Laporan ini dihasilkan pada: {{ \Carbon\Carbon::now()->locale('id')->isoFormat('D MMMM YYYY, HH:mm:ss') }}
        </div>
    </div>
    </div>
</body>

</html>
