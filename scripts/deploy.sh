#!/usr/bin/env bash
# ==============================================================================
# Script Deployment Otomatis KampusLMS — Kelompok 06
# Sesuai Pedoman Modul 12 (Milestone M4: Production Ready)
# ==============================================================================
set -e

echo "🚀 [$(date '+%Y-%m-%d %H:%M:%S')] Memulai proses deployment..."

# 1. Aktifkan Maintenance Mode (pengunjung dialihkan sementara)
echo "🔒 Mengaktifkan maintenance mode..."
php artisan down --retry=60 || true

# 2. Ambil perubahan kode terbaru dari branch main
echo "📥 Mengambil kode terbaru dari Git..."
git fetch origin main
git reset --hard origin/main

# 3. Instal dependensi PHP produksi (tanpa dev package)
echo "📦 Menginstal dependensi Composer produksi..."
composer install --no-dev --optimize-autoloader --no-interaction

# 4. Instal dependensi JavaScript & kompilasi aset Vite
echo "🎨 Membangun aset frontend (Vite / Tailwind)..."
npm ci --prefer-offline --no-audit
npm run build

# 5. Jalankan migrasi database (pakai --force untuk lingkungan produksi)
echo "🗄️ Menjalankan migrasi database..."
php artisan migrate --force

# 6. Bersihkan dan bangun ulang cache untuk performa maksimal
echo "⚡ Mengoptimalkan cache Laravel 12..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 7. Pastikan symbolic link storage terpasang
echo "🔗 Memeriksa storage symlink..."
php artisan storage:link || true

# 8. Restart background queue worker (Supervisor)
echo "🔄 Me-restart queue worker..."
php artisan queue:restart || true

# 9. Matikan Maintenance Mode (aplikasi kembali online)
echo "🔓 Mematikan maintenance mode..."
php artisan up

echo "✅ [$(date '+%Y-%m-%d %H:%M:%S')] Deployment KampusLMS berhasil diselesaikan!"
