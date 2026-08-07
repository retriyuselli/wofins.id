<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Undangan Akun Tim — {{ config('app.name') }}</title>
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
        .btn {
            display: inline-block;
            margin-top: 8px;
            background: #c9a227;
            color: #071526 !important;
            text-decoration: none;
            font-weight: 800;
            font-size: 14px;
            padding: 12px 22px;
            border-radius: 999px;
        }
        .footer {
            margin-top: 24px;
            font-size: 12px;
            color: #5c6675;
            text-align: center;
        }
        .gold { color: #c9a227; font-weight: 700; }
        .note {
            font-size: 12px;
            color: #5c6675;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <p>WOFINS</p>
            <h1>Undangan bergabung ke tim</h1>
        </div>

        <p class="lead">
            Halo <strong>{{ $user->name }}</strong>, akun WOFINS Anda telah dibuat oleh
            <strong>{{ $inviterName }}</strong>. Silakan login dengan email di bawah — jangan langsung menganggap sesi sudah aktif;
            Anda perlu masuk sendiri melalui halaman login.
        </p>

        <div class="box">
            <h2>Data login</h2>
            <div class="summary-line">
                <span class="label">Email</span> : <span class="value">{{ $user->email }}</span>
            </div>
            @if (! empty($plainPassword))
                <div class="summary-line">
                    <span class="label">Password sementara</span> : <span class="value">{{ $plainPassword }}</span>
                </div>
            @endif
            <div class="summary-line">
                <span class="label">Dibuat oleh</span> : <span class="value">{{ $inviterName }}</span>
            </div>
        </div>

        <div class="box" style="text-align: center;">
            <h2>Langkah selanjutnya</h2>
            <p class="lead" style="margin-bottom: 12px;">
                Buka halaman login, masukkan email dan password di atas, lalu mulai menggunakan WOFINS.
            </p>
            <a href="{{ $loginUrl }}" class="btn">Login ke WOFINS</a>
            <p style="margin-top: 14px; font-size: 12px; color: #5c6675;">
                Atau buka tautan ini:<br>
                <a href="{{ $loginUrl }}" style="color: #0b1f3a;">{{ $loginUrl }}</a>
            </p>
            <p class="note" style="margin-top: 14px;">
                Disarankan mengganti password setelah login pertama kali.
            </p>
        </div>

        <p class="lead">
            Jika Anda tidak mengharapkan email ini, hubungi
            <span class="gold">support@wofins.id</span>
            atau pemilik akun yang mengundang Anda.
        </p>

        <p class="footer">
            Email otomatis dari {{ config('app.name') }}.
        </p>
    </div>
</body>
</html>
