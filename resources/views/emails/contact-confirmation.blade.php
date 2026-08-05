<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Pesan — {{ config('app.name') }}</title>
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
        .summary-line .label {
            color: #5c6675;
            font-weight: 400;
        }
        .summary-line .value {
            color: #0b1f3a;
            font-weight: 600;
        }
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <p>WOFINS</p>
            <h1>Pesan Anda sudah kami terima</h1>
        </div>

        <p class="lead">
            Halo <strong>{{ $inquiry->name }}</strong>, terima kasih telah menghubungi WOFINS.
            Data permintaan Anda sudah berhasil kami catat dan akan segera ditindaklanjuti oleh tim admin.
        </p>

        <div class="box">
            <h2>Ringkasan permintaan</h2>
            <div class="summary-line">
                <span class="label">Kebutuhan</span> : <span class="value">{{ $inquiry->need }}</span>
            </div>
            @if ($inquiry->company)
                <div class="summary-line">
                    <span class="label">Perusahaan / WO</span> : <span class="value">{{ $inquiry->company }}</span>
                </div>
            @endif
            @if ($inquiry->paket)
                <div class="summary-line">
                    <span class="label">Paket</span> : <span class="value">{{ $inquiry->paket }}</span>
                </div>
            @endif
            <div class="summary-line">
                <span class="label">Email</span> : <span class="value">{{ $inquiry->email }}</span>
            </div>
            <div class="summary-line">
                <span class="label">WhatsApp</span> : <span class="value">{{ $inquiry->phone }}</span>
            </div>
        </div>

        <div class="box">
            <h2>Langkah selanjutnya</h2>
            <ol class="steps">
                <li>Tim admin meninjau kebutuhan Anda.</li>
                <li>Kami akan menghubungi Anda melalui email atau WhatsApp dalam 1–2 hari kerja.</li>
                <li>Jika diperlukan, kami akan menjadwalkan demo atau sesi konsultasi.</li>
            </ol>
        </div>

        <p class="lead">
            Apabila ada informasi tambahan, cukup balas email ini atau hubungi kami di
            <span class="gold">support@wofins.id</span> / WhatsApp <span class="gold">+62 813-7318-3794</span>.
        </p>

        <p class="footer">
            Email otomatis dari {{ config('app.name') }}. Mohon tidak membalas jika Anda tidak mengirim formulir kontak.
        </p>
    </div>
</body>
</html>
