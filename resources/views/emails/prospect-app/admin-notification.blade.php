<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Prospek Baru — {{ config('app.name') }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #1a2332;
            max-width: 640px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f7f4ee;
        }
        .container {
            background: #fff;
            border-radius: 12px;
            padding: 28px;
            border: 1px solid #e6e2d9;
        }
        .header {
            background: linear-gradient(145deg, #0b1f3a 0%, #14335a 100%);
            color: #fff;
            padding: 18px 22px;
            border-radius: 10px;
            margin-bottom: 22px;
        }
        .header p {
            margin: 0;
            font-size: 12px;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #e8d48b;
        }
        .header h1 {
            margin: 6px 0 0;
            font-size: 20px;
        }
        .box {
            background: #f7f4ee;
            border: 1px solid #e6e2d9;
            border-radius: 10px;
            padding: 16px 18px;
            margin-bottom: 18px;
        }
        .box h2 {
            margin: 0 0 10px;
            font-size: 14px;
            color: #0b1f3a;
        }
        .summary-line {
            padding: 8px 0;
            border-bottom: 1px solid #e6e2d9;
            font-size: 14px;
            color: #0b1f3a;
            line-height: 1.5;
        }
        .summary-line:last-child { border-bottom: none; }
        .summary-line .label { color: #5c6675; }
        .summary-line .value { color: #0b1f3a; font-weight: 600; }
        .message-box {
            margin-top: 8px;
            padding: 14px;
            background: #fff;
            border-radius: 8px;
            border: 1px solid #e6e2d9;
            white-space: pre-wrap;
            font-weight: 400;
        }
        .footer {
            margin-top: 24px;
            font-size: 12px;
            color: #5c6675;
        }
    </style>
</head>
<body>
@php
    $serviceLabel = \App\Support\PricingPlans::optionLabel($prospect->service);
@endphp
    <div class="container">
        <div class="header">
            <p>Form Pendaftaran Website</p>
            <h1>Prospek baru dari {{ $prospect->full_name }}</h1>
        </div>

        <div class="box">
            <h2>Ringkasan permintaan</h2>
            <div class="summary-line">
                <span class="label">Nama lengkap</span> : <span class="value">{{ $prospect->full_name }}</span>
            </div>
            <div class="summary-line">
                <span class="label">Perusahaan</span> : <span class="value">{{ $prospect->company_name }}</span>
            </div>
            <div class="summary-line">
                <span class="label">Departemen</span> : <span class="value">{{ $prospect->industry?->industry_name ?? '—' }}</span>
            </div>
            <div class="summary-line">
                <span class="label">Jumlah karyawan</span> : <span class="value">{{ $prospect->user_size }}</span>
            </div>
            <div class="summary-line">
                <span class="label">Email</span> : <span class="value"><a href="mailto:{{ $prospect->email }}">{{ $prospect->email }}</a></span>
            </div>
            <div class="summary-line">
                <span class="label">Nomor ponsel</span> : <span class="value">{{ $prospect->phone }}</span>
            </div>
            <div class="summary-line">
                <span class="label">Paket layanan</span> : <span class="value">{{ $serviceLabel }}</span>
            </div>
            <div class="summary-line">
                <span class="label">Kebutuhan &amp; tantangan bisnis</span> :
                <div class="message-box">{{ $prospect->reason_for_interest }}</div>
            </div>
        </div>

        <p class="footer">Email otomatis dari formulir pendaftaran {{ config('app.name') }}.</p>
    </div>
</body>
</html>
