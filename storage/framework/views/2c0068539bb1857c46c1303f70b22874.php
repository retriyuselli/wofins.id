<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Email — <?php echo e(config('app.name')); ?></title>
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
            text-align: center;
        }
        .box h2 {
            margin: 0 0 10px;
            font-size: 14px;
            color: #0b1f3a;
        }
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <p>WOFINS</p>
            <h1>Verifikasi email Anda</h1>
        </div>

        <p class="lead">
            Halo <strong><?php echo e($user->name); ?></strong>, terima kasih sudah mendaftar di WOFINS.
            Silakan verifikasi alamat email Anda untuk mengaktifkan akses akun.
        </p>

        <div class="box">
            <h2>Konfirmasi alamat email</h2>
            <p class="lead" style="margin-bottom: 12px;">
                Klik tombol di bawah. Tautan berlaku sekitar 60 menit.
            </p>
            <a href="<?php echo e($url); ?>" class="btn">Verifikasi Email</a>
            <p style="margin-top: 14px; font-size: 12px; color: #5c6675;">
                Atau buka tautan ini:<br>
                <a href="<?php echo e($url); ?>" style="color: #0b1f3a; word-break: break-all;"><?php echo e($url); ?></a>
            </p>
        </div>

        <p class="lead">
            Jika Anda tidak mendaftar di WOFINS, abaikan email ini.
            Butuh bantuan? Hubungi <span class="gold">support@wofins.id</span>.
        </p>

        <p class="footer">
            Email otomatis dari <?php echo e(config('app.name')); ?>.
        </p>
    </div>
</body>
</html>
<?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/application/wofins/resources/views/emails/verify-email.blade.php ENDPATH**/ ?>