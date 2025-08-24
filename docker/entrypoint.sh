#!/usr/bin/env bash
set -e

# 1) Pastikan folder ada
mkdir -p storage/logs \
         storage/framework/{cache,sessions,views} \
         bootstrap/cache

# 2) Bersihkan cache lama (biar nggak bawa cache dev)
rm -f bootstrap/cache/*.php || true

# 3) Discover package sesuai vendor image (tanpa dev)
php artisan package:discover --ansi || true

# 4) Optimisasi kalau APP_KEY ada
if [ -n "${APP_KEY:-}" ]; then
  php artisan storage:link || true

  php artisan optimize:clear || true
  php artisan optimize || true
fi

# 5) Kembalikan izin ke user web
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

exec "$@"
