#!/bin/zsh
# Bind 0.0.0.0 agar Simulator + device fisik bisa konek
cd /Applications/MAMP/htdocs/wofins
exec php artisan serve --host=0.0.0.0 --port=8000
