# syntax=docker/dockerfile:1.7
#
# Image produksi moso: Laravel Octane di FrankenPHP (PHP 8.5) dan server SSR Inertia (Node).
# Panduan lengkap: docs/DEPLOY.md
#
#   docker build --target app -t moso-app .
#   docker build --target ssr -t moso-ssr .

ARG PHP_IMAGE=dunglas/frankenphp:1.13.1-php8.5.11-trixie
ARG NODE_IMAGE=node:24.20.0-trixie-slim
ARG COMPOSER_IMAGE=composer:2.10.2

FROM ${COMPOSER_IMAGE} AS composer-bin
FROM ${NODE_IMAGE} AS node-bin

# ---------------------------------------------------------------------------
# Basis PHP: FrankenPHP + ekstensi yang dipakai aplikasi.
# gd (re-encode gambar WebP), intl, zip, pdo_mysql, redis (phpredis), pcntl (Octane).
# OPcache sudah bawaan PHP 8.5.
# ---------------------------------------------------------------------------
FROM ${PHP_IMAGE} AS php-base

RUN install-php-extensions \
        bcmath \
        exif \
        gd \
        intl \
        pcntl \
        pdo_mysql \
        redis \
        zip

COPY --from=composer-bin /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_INTERACTION=1

WORKDIR /app

# ---------------------------------------------------------------------------
# Dependensi PHP produksi (tanpa paket dev seperti Boost dan Pest).
# ---------------------------------------------------------------------------
FROM php-base AS vendor

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-progress

# ---------------------------------------------------------------------------
# Aset frontend dan bundle SSR. Butuh PHP karena plugin Wayfinder menjalankan artisan.
# ---------------------------------------------------------------------------
FROM php-base AS assets

COPY --from=node-bin /usr/local/bin/node /usr/local/bin/node
COPY --from=node-bin /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

COPY package.json package-lock.json .npmrc ./
COPY packages ./packages
RUN npm ci --no-audit --no-fund

COPY --from=vendor /app/vendor ./vendor
COPY . .
RUN composer dump-autoload --optimize

# Tema yang dikompilasi dan nama aplikasi di bundle ditentukan saat build.
ARG VITE_THEMES=TemaModern
ARG VITE_APP_NAME="Portal Dinas"
ENV VITE_THEMES=${VITE_THEMES} \
    VITE_APP_NAME=${VITE_APP_NAME}

RUN npm run build:ssr \
    && npm prune --omit=dev --no-audit --no-fund

# ---------------------------------------------------------------------------
# Runtime aplikasi: Octane + FrankenPHP, berjalan sebagai www-data.
# Dipakai juga untuk container queue worker dan scheduler (command berbeda).
# ---------------------------------------------------------------------------
FROM php-base AS app

ENV APP_ENV=production \
    APP_DEBUG=false \
    OCTANE_SERVER=frankenphp \
    XDG_CONFIG_HOME=/config \
    XDG_DATA_HOME=/data

RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/deploy/app/php.ini "$PHP_INI_DIR/conf.d/zz-moso.ini"
COPY --from=vendor /app/vendor ./vendor
COPY . .
COPY --from=assets /app/public/build ./public/build
# Inertia hanya memanggil server SSR bila berkas bundle-nya ada di sini (ssr.ensure_bundle_exists).
COPY --from=assets /app/bootstrap/ssr ./bootstrap/ssr
COPY docker/deploy/app/entrypoint.sh /usr/local/bin/moso-entrypoint

RUN composer dump-autoload --no-dev --classmap-authoritative \
    && rm /usr/bin/composer \
    # Skrip worker Octane disiapkan saat build: public/ milik root, container berjalan sebagai www-data.
    && cp vendor/laravel/octane/src/Commands/stubs/frankenphp-worker.php public/frankenphp-worker.php \
    && chmod +x /usr/local/bin/moso-entrypoint \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs \
    && chown -R www-data:www-data storage bootstrap/cache /config /data \
    # Port 8000 tidak butuh hak root; buang capability agar jalan di runtime yang ketat.
    && setcap -r /usr/local/bin/frankenphp

USER www-data

EXPOSE 8000

ENTRYPOINT ["moso-entrypoint"]
CMD ["php", "artisan", "octane:frankenphp", "--host=0.0.0.0", "--port=8000", "--admin-port=2019", "--max-requests=500"]

# ---------------------------------------------------------------------------
# Server SSR Inertia. Hanya untuk jaringan internal (render halaman publik untuk SEO).
# ---------------------------------------------------------------------------
FROM ${NODE_IMAGE} AS ssr

ENV NODE_ENV=production

WORKDIR /app

COPY --from=assets --chown=node:node /app/node_modules ./node_modules
COPY --from=assets --chown=node:node /app/packages ./packages
COPY --from=assets --chown=node:node /app/bootstrap/ssr ./bootstrap/ssr
COPY --from=assets --chown=node:node /app/package.json ./package.json

USER node

EXPOSE 13714

CMD ["node", "bootstrap/ssr/app.js"]
