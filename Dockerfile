# ---------- Stage 1: Composer (pakai PHP 8.3 di dalam image composer) ----------
FROM composer:2 AS vendor
WORKDIR /app

# Kalau di stage ini ext-intl tidak tersedia, paling aman abaikan requirementnya.
# Runtime kita nanti memasang intl, jadi aman.
COPY composer.json composer.lock ./
RUN composer install \
  --no-dev --no-interaction --no-ansi --no-progress \
  --prefer-dist --optimize-autoloader --no-scripts \
  --ignore-platform-req=ext-intl

# ---------- Stage 2: Frontend build (Vite -> public/build) ----------
FROM node:20-alpine AS front
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY resources resources
COPY public public
COPY vite.config.* ./
#COPY tailwind.config.* postcss.config.* ./
RUN npm run build

# ---------- Stage 2.5: Ambil binary Caddy yang resmi ----------
FROM caddy:2-alpine AS caddybin

# ---------- Stage 3: Runtime (PHP-FPM 8.3 + Caddy + Reverb via supervisord) ----------
FROM php:8.3-fpm-alpine

WORKDIR /var/www/html

# System deps
RUN apk add --no-cache \
    git unzip icu-dev libzip-dev oniguruma-dev \
    libpng-dev libjpeg-turbo-dev freetype-dev libwebp-dev \
    bash tzdata supervisor

RUN apk add --no-cache $PHPIZE_DEPS \
 && pecl install redis \
 && docker-php-ext-enable redis \
 && apk del $PHPIZE_DEPS

# PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
 && docker-php-ext-install -j$(nproc) \
    bcmath intl pcntl pdo_mysql zip gd exif opcache

# Opcache tuning
RUN { \
  echo 'opcache.enable=1'; \
  echo 'opcache.enable_cli=1'; \
  echo 'opcache.jit=1255'; \
  echo 'opcache.jit_buffer_size=128M'; \
  echo 'opcache.memory_consumption=192'; \
  echo 'opcache.max_accelerated_files=100000'; \
} > /usr/local/etc/php/conf.d/opcache-recommended.ini

RUN { \
  echo '[www]'; \
  echo 'pm = dynamic'; \
  echo 'pm.max_children = 20'; \
  echo 'pm.start_servers = 4'; \
  echo 'pm.min_spare_servers = 2'; \
  echo 'pm.max_spare_servers = 6'; \
  echo 'pm.max_requests = 500'; \
  echo 'clear_env = no'; \
} > /usr/local/etc/php-fpm.d/zz-pool.conf

RUN { \
  echo 'opcache.validate_timestamps=0'; \
  echo 'realpath_cache_size=4096K'; \
  echo 'realpath_cache_ttl=600'; \
} > /usr/local/etc/php/conf.d/opcache-prod.ini

# Aplikasi
COPY . /var/www/html

# Vendor & build assets
COPY --from=vendor /app/vendor /var/www/html/vendor
COPY --from=front  /app/dist /var/www/html/public/build

# Pastikan caddy ada di lokasi yang supervisor pakai
COPY --from=caddybin /usr/bin/caddy /usr/bin/caddy

# Config proses
COPY docker/Caddyfile /etc/caddy/Caddyfile
RUN /usr/bin/caddy fmt --overwrite /etc/caddy/Caddyfile

COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

ENV PORT=8080
EXPOSE 8080

# Direct PHP errors ke STDERR
RUN printf "log_errors=On\nerror_log=/proc/self/fd/2\ndisplay_errors=Off\n" > /usr/local/etc/php/conf.d/error-log.ini

ENTRYPOINT ["entrypoint"]
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
