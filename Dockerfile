# ==============================================================================
# Dockerfile KampusLMS — Kelompok 06 (Laravel 12 & PHP 8.4)
# Production-ready untuk Railway.app
# ==============================================================================

# ------------------------------------------------------------------------------
# Tahap 1: Kompilasi Aset Frontend (Node.js 20 - Vite & Tailwind CSS)
# ------------------------------------------------------------------------------
FROM node:20-alpine AS node_builder

WORKDIR /app
COPY package*.json ./
RUN npm ci --prefer-offline --no-audit

COPY . .
RUN npm run build

# ------------------------------------------------------------------------------
# Tahap 2: Lingkungan Server PHP 8.4 Apache (Production Ready)
# ------------------------------------------------------------------------------
FROM php:8.4-apache

# 1. Install ekstensi sistem & library PHP yang dibutuhkan Laravel, serta Supervisor & Sudo
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    curl \
    libzip-dev \
    supervisor \
    sudo \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 2. Konfigurasi Apache: aktifkan mod_rewrite, pastikan hanya mpm_prefork yang aktif (hindari error More than one MPM loaded)
RUN a2enmod rewrite \
    && rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.* \
    && a2enmod mpm_prefork \
    && sed -ri -e 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# 3. Atur Document Root ke /var/www/html/public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 4. Install Composer resmi
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 5. Salin seluruh kode proyek ke container
WORKDIR /var/www/html
COPY . .

# 6. Salin hasil kompilasi aset frontend dari Tahap 1 (Vite manifest & CSS/JS)
COPY --from=node_builder /app/public/build /var/www/html/public/build

# 7. Install dependensi composer untuk mode produksi (tambahkan --ignore-platform-reqs agar selalu kompatibel)
RUN composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs

# 8. Konfigurasi hak akses storage & konfigurasi Supervisor Worker
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache \
    && mkdir -p /var/log/supervisor /etc/supervisor/conf.d \
    && printf "[program:kampuslms-worker]\nprocess_name=%%(program_name)s_%%(process_num)02d\ncommand=php /var/www/html/artisan queue:work database --sleep=3 --tries=3 --max-time=3600\nautostart=true\nautorestart=true\nuser=root\nnumprocs=2\nredirect_stderr=true\nstdout_logfile=/var/www/html/storage/logs/worker.log\nstdout_logfile_maxbytes=10MB\nstdout_logfile_backups=5\nstopwaitsecs=3600\n" > /etc/supervisor/conf.d/kampuslms-worker.conf

EXPOSE 80 8080

# 9. Script startup: atur PORT dinamis, clear cache, migrate & seed, jalankan Supervisor daemon worker, lalu jalankan Apache
CMD ["sh", "-c", "rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.* && export PORT=\"${PORT:-8080}\" && sed -i \"s/Listen .*/Listen $PORT/\" /etc/apache2/ports.conf && sed -i \"s/<VirtualHost \\*:.*>/<VirtualHost \\*:$PORT>/\" /etc/apache2/sites-available/000-default.conf && php artisan config:clear && php artisan route:clear && php artisan view:clear && (php artisan cache:clear || true) && (php artisan storage:link || true) && (php artisan migrate --force || true) && (php artisan db:seed --force || true) && (/usr/bin/supervisord -c /etc/supervisor/supervisord.conf || true) && exec apache2-foreground"]
