<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan dalam proses — {{ config('app.name') }}</title>
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
            padding: 22px;
            border-radius: 10px;
            margin-bottom: 22px;
            text-align: center;
        }
        .header p {
            margin: 0;
            font-size: 12px;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #e8d48b;
        }
        .header h1 {
            margin: 8px 0 0;
            font-size: 22px;
        }
        .lead {
            font-size: 15px;
            color: #5c6675;
            margin: 0 0 20px;
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
        .steps {
            margin: 0;
            padding-left: 18px;
            color: #1a2332;
            font-size: 14px;
        }
        .steps li { margin-bottom: 8px; }
        .footer {
            margin-top: 24px;
            font-size: 12px;
            color: #5c6675;
            text-align: center;
        }
        .gold { color: #c9a227; font-weight: 700; }
        .status {
            display: inline-block;
            background: #fef3c7;
            color: #92400e;
            font-size: 12px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 999px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <p>WOFINS</p>
            <h1>Pesanan Anda sedang diproses</h1>
        </div>

        <p class="lead">
            Halo <strong>{{ $order->full_name }}</strong>, terima kasih.
            Pesanan paket dan bukti pembayaran Anda sudah kami terima.
            Aplikasi / aktivasi paket Anda <strong>sedang dalam proses peninjauan</strong> oleh tim admin.
        </p>

        <p style="margin: 0 0 18px;">
            <span class="status">Status: Menunggu tinjauan</span>
        </p>

        <div class="box">
            <h2>Ringkasan pesanan</h2>
            <div class="summary-line">
                <span class="label">Kode pesanan</span> : <span class="value">{{ $order->order_code }}</span>
            </div>
            <div class="summary-line">
                <span class="label">Paket</span> : <span class="value">{{ $order->plan_name }}</span>
            </div>
            <div class="summary-line">
                <span class="label">Durasi</span> : <span class="value">{{ $order->billing_label }}</span>
            </div>
            <div class="summary-line">
                <span class="label">Total transfer</span> : <span class="value">{{ $order->formatted_amount }}</span>
            </div>
            @if ((int) $order->unique_amount > 0)
                <div class="summary-line">
                    <span class="label">Kode unik</span> : <span class="value">{{ $order->formatted_unique_amount }}</span>
                </div>
            @endif
            @if ($order->company_name)
                <div class="summary-line">
                    <span class="label">Perusahaan / WO</span> : <span class="value">{{ $order->company_name }}</span>
                </div>
            @endif
        </div>

        <div class="box">
            <h2>Langkah selanjutnya</h2>
            <ol class="steps">
                <li>Tim admin memverifikasi bukti pembayaran Anda.</li>
                <li>Setelah disetujui, paket WOFINS akan diaktifkan.</li>
                <li>Kami akan menginformasikan hasilnya melalui email atau WhatsApp.</li>
            </ol>
        </div>

        <p class="lead">
            Simpan kode pesanan <strong>{{ $order->order_code }}</strong> jika menghubungi support.
            Butuh bantuan? Hubungi
            <span class="gold">support@wofins.id</span> / WhatsApp <span class="gold">+62 813-7318-3794</span>.
        </p>

        <p class="footer">
            Email otomatis dari {{ config('app.name') }}. Mohon abaikan jika Anda tidak mengirim pesanan paket.
        </p>
    </div>
</body>
</html>
