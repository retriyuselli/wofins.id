<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesan Kontak — {{ config('app.name') }}</title>
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
        .row {
            margin-bottom: 14px;
        }
        .label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #5c6675;
            margin-bottom: 4px;
        }
        .value {
            font-size: 15px;
            color: #0b1f3a;
            font-weight: 600;
        }
        .message-box {
            margin-top: 8px;
            padding: 14px;
            background: #f7f4ee;
            border-radius: 8px;
            border: 1px solid #e6e2d9;
            white-space: pre-wrap;
            font-weight: 400;
            color: #1a2332;
        }
        .footer {
            margin-top: 24px;
            font-size: 12px;
            color: #5c6675;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <p>Form Kontak Website</p>
            <h1>Pesan baru dari {{ $data['name'] }}</h1>
        </div>

        <div class="row">
            <span class="label">Nama</span>
            <div class="value">{{ $data['name'] }}</div>
        </div>

        @if (! empty($data['company']))
            <div class="row">
                <span class="label">Perusahaan / WO</span>
                <div class="value">{{ $data['company'] }}</div>
            </div>
        @endif

        <div class="row">
            <span class="label">Email</span>
            <div class="value"><a href="mailto:{{ $data['email'] }}">{{ $data['email'] }}</a></div>
        </div>

        <div class="row">
            <span class="label">WhatsApp</span>
            <div class="value">{{ $data['phone'] }}</div>
        </div>

        <div class="row">
            <span class="label">Kebutuhan</span>
            <div class="value">{{ $data['need'] }}</div>
        </div>

        @if (! empty($data['paket']))
            <div class="row">
                <span class="label">Paket</span>
                <div class="value">{{ $data['paket'] }}</div>
            </div>
        @endif

        <div class="row">
            <span class="label">Pesan</span>
            <div class="value message-box">{{ $data['message'] }}</div>
        </div>

        <p class="footer">Email ini dikirim otomatis dari formulir kontak {{ config('app.name') }}.</p>
    </div>
</body>
</html>
