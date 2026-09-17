<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="refresh" content="0;url={{ $redirectUrl }}">
    <title>Mengalihkan — WOFINS</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            font-family: system-ui, -apple-system, sans-serif;
            background: #f4f6f9;
            color: #0b1f3a;
            padding: 24px;
            text-align: center;
        }
        .card {
            max-width: 360px;
            background: #fff;
            border-radius: 16px;
            padding: 28px 24px;
            box-shadow: 0 12px 40px rgba(11, 31, 58, 0.08);
        }
        p { margin: 0 0 12px; line-height: 1.5; }
        .muted { color: #6b7280; font-size: 0.9rem; }
        a {
            display: inline-block;
            margin-top: 8px;
            color: #0b1f3a;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="card">
        <p><strong>WOFINS</strong></p>
        @if (! empty($message))
            <p>{{ $message }}</p>
        @else
            <p>Login Apple berhasil. Mengalihkan…</p>
        @endif
        <p class="muted">Jika tidak berpindah otomatis, klik tautan di bawah.</p>
        <a href="{{ $redirectUrl }}">Lanjutkan ke WOFINS</a>
    </div>
    <script>
        window.location.replace(@json($redirectUrl));
    </script>
</body>
</html>
