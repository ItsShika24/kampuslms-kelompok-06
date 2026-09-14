# Deployment Guide — KampusLMS

## Akun Demo Produksi

Akun-akun berikut tersedia untuk keperluan pengujian oleh penguji/evaluator.

> **Catatan Keamanan**: File ini **boleh** dibaca oleh penguji, tetapi **tidak** boleh
> dipublish di README.md atau tempat publik lainnya. Kata sandi di bawah wajib
> diganti setelah evaluasi selesai.

| Role | Email | Kata Sandi Default |
|------|-------|-------------------|
| Admin | `admin@kampuslms.test` | `KampusLMS@Admin2025!` |
| Dosen | `dosen@kampuslms.test` | `KampusLMS@Dosen2025!` |
| Mahasiswa | `mahasiswa@kampuslms.test` | `KampusLMS@Mhs2025!` |

---

## Cara Update Kata Sandi Demo di Produksi

1. Set variabel environment `DEMO_PASSWORD` di `.env` produksi:
   ```env
   DEMO_PASSWORD=KataS@ndiBaruAnda2025!
   ```

2. Jalankan seeder khusus:
   ```bash
   php artisan db:seed --class=DemoAccountSeeder --force
   ```

3. Seeder akan memperbarui kata sandi ketiga akun demo sesuai `DEMO_PASSWORD`.

---

## Prasyarat Deployment

```bash
# 1. Install dependencies
composer install --optimize-autoloader --no-dev

# 2. Generate app key (jika belum ada)
php artisan key:generate

# 3. Jalankan migrasi
php artisan migrate --force

# 4. Seed data dasar
php artisan db:seed --force

# 5. Update password akun demo
php artisan db:seed --class=DemoAccountSeeder --force

# 6. Cache config & routes
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Environment Variables Wajib

```env
APP_ENV=production
APP_KEY=base64:...          # generate via: php artisan key:generate
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kampuslms
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

DEMO_PASSWORD=GantiDenganKataSandiKuat!
```

---

## Catatan Keamanan Pasca-Evaluasi

Setelah evaluasi selesai:
1. Ganti kata sandi semua akun demo.
2. Pertimbangkan menonaktifkan akun `mahasiswa@kampuslms.test` dan `dosen@kampuslms.test` jika tidak diperlukan.
3. Pastikan `APP_DEBUG=false` di produksi.
