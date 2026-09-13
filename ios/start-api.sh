#!/bin/zsh
# Bind 0.0.0.0 agar Simulator + device fisik bisa konek
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"
exec php artisan serve --host=0.0.0.0 --port=8000
