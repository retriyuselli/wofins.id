# Wofins iOS — Run dari Xcode

Aplikasi SwiftUI ESS. **Jalankan hanya lewat Xcode** (⌘R). Cursor dipakai untuk backend Laravel saja.

## 1. Pastikan API lokal nyala

Di Terminal (atau Cursor terminal):

```bash
cd /Applications/MAMP/htdocs/wofins
php artisan serve --host=0.0.0.0 --port=8000
```

(atau `ios/start-api.sh`)

Cek: `http://127.0.0.1:8000/up` → harus OK.

## 2. Buka project di Xcode

```text
/Applications/MAMP/htdocs/wofins/ios/Wofins.xcodeproj
```

Atau Finder → `wofins/ios` → double-click `Wofins.xcodeproj`.

## 3. Run

1. Toolbar Xcode: pilih scheme **Wofins**
2. Destination: **iPhone 17** (Simulator) — atau device fisik Anda
3. Tekan **Run** (⌘R)

Di layar login harus muncul:
- Simulator: `Simulator → http://127.0.0.1:8000`
- Device: `Device → http://192.168.1.3:8000`  
Login dengan email/password user yang ada di database lokal.

## 4. Signing (sekali saja)

Xcode → target **Wofins** → **Signing & Capabilities**  
- Centang **Automatically manage signing**  
- Pilih **Team** Apple Developer Anda  

Team ID sudah di-set di project (`R776QJ2P94`). Jika Xcode minta ganti, pilih team yang sama di UI.

## Device fisik (iPhone asli)

App otomatis memakai IP Mac: `http://192.168.1.3:8000`  
Pastikan API di-bind ke semua interface:

```bash
cd /Applications/MAMP/htdocs/wofins
php artisan serve --host=0.0.0.0 --port=8000
```

atau: `ios/start-api.sh`

iPhone & Mac harus satu Wi‑Fi. Jika IP Mac berubah, edit `Wofins/Config/APIConfig.swift`.

## Simulator

Otomatis memakai `http://127.0.0.1:8000`.

## Regenerasi project (jika ubah project.yml)

```bash
cd /Applications/MAMP/htdocs/wofins/ios
xcodegen generate
```

Buka ulang `Wofins.xcodeproj` di Xcode.
