# Wofins iOS — Run dari Xcode

Aplikasi SwiftUI keuangan WO. **Jalankan hanya lewat Xcode** (⌘R). Cursor dipakai untuk backend Laravel.

## 1. Pastikan API lokal nyala

Di Terminal (folder proyek Laravel):

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/application/wofins
php artisan serve --host=0.0.0.0 --port=8000
```

atau `composer run dev`, atau `ios/start-api.sh`.

Cek: `http://127.0.0.1:8000/up` → harus OK.

## 2. Buka project di Xcode

```text
/Applications/XAMPP/xamppfiles/htdocs/application/wofins/ios/Wofins.xcodeproj
```

Atau Finder → `wofins/ios` → double-click `Wofins.xcodeproj`.

## 3. Run

1. Toolbar Xcode: pilih scheme **Wofins**
2. Destination: Simulator iPhone — atau device fisik Anda
3. Tekan **Run** (⌘R)

Di layar login harus muncul:
- Simulator: `Simulator → http://127.0.0.1:8000`
- Device: `Device → http://192.168.1.13:8000`

Login dengan email/password user yang ada di database lokal.

## 4. Signing (sekali saja)

Xcode → target **Wofins** → **Signing & Capabilities**  
- Centang **Automatically manage signing**  
- Pilih **Team** Apple Developer Anda  

Team ID sudah di-set di project (`R776QJ2P94`). Jika Xcode minta ganti, pilih team yang sama di UI.

## Device fisik (iPhone asli)

App memakai IP Mac: `http://192.168.1.13:8000`  
Pastikan API di-bind ke semua interface:

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/application/wofins
php artisan serve --host=0.0.0.0 --port=8000
```

atau: `ios/start-api.sh`

iPhone & Mac harus satu Wi‑Fi. Jika IP Mac berubah, edit `Wofins/Config/APIConfig.swift` dan exception ATS di `Wofins/Info.plist` / `project.yml`.

## Simulator

Otomatis memakai `http://127.0.0.1:8000`.

## Release / TestFlight

`BASE_URL` Release: `https://app.wofins.id` (API customer, bukan situs marketing `wofins.id`).

## Login Google

Layar login punya tombol **Google**. Alur: Google Sign-In SDK → ID token → `POST /api/v1/auth/google`.

Di [Google Cloud Console](https://console.cloud.google.com/apis/credentials) (project yang sama dengan login web):

1. Buat OAuth client **iOS**
2. Bundle ID: `id.wofins.app`
3. Isi `GOOGLE_IOS_CLIENT_ID` di `.env` dengan Client ID itu, lalu `php artisan config:clear`
4. Ganti `GID_CLIENT_ID` di Xcode (Debug/Release) ke Client ID iOS itu jika sudah dibuat

Sementara app memakai Client ID **Web** yang sudah ada. URL scheme `com.googleusercontent.apps.…` sudah terdaftar di Info.plist.

Kalau iPhone fisik gagal konek (`192.168.1.13:8000`), restart API:

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/application/wofins
composer run dev
```

(`php artisan serve` sekarang bind `0.0.0.0` supaya HP satu Wi‑Fi bisa masuk.)

## Regenerasi project (jika ubah project.yml)

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/application/wofins/ios
xcodegen generate
```

Buka ulang `Wofins.xcodeproj` di Xcode.
