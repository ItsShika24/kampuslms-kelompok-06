# Deployment Guide — KampusLMS (Kelompok 06)

> **Panduan Resmi Deployment, Konfigurasi Server & Otomasi CI/CD Menuju Lingkungan Produksi**  
> Sesuai Standar Modul 12 (Milestone M4: *Production Ready*) — Laravel 12 & Nginx/Ubuntu  
> **Penanggung Jawab Infrastruktur:** Tika Mila Wahyuni (10241070)  

---

## 🖥️ 1. Spesifikasi Server Produksi

Berikut adalah spesifikasi lingkungan server hosting/VPS produksi yang digunakan untuk deployment aplikasi KampusLMS:

| Komponen | Spesifikasi / Versi | Keterangan |
|---|---|---|
| **Sistem Operasi** | Ubuntu 24.04 LTS (Noble Numbat) x86_64 | Linux kernel 6.8+ |
| **Spesifikasi Hardware** | 2 vCPU / 4 GB RAM / 40 GB NVMe SSD | Standard Cloud VPS |
| **Web Server** | Nginx 1.24+ | Reverse proxy & static file handler |
| **PHP Runtime** | PHP 8.4-FPM (FastCGI Process Manager) | Ekstensi: pdo_mysql, mbstring, bcmath, curl, xml, zip |
| **Database Server** | MySQL 8.0.36 / MariaDB 11.2 | InnoDB Engine, utf8mb4 |
| **Process Manager** | Supervisor 4.2+ | Daemon background queue worker |
| **SSL / TLS Certificate**| Let's Encrypt Wildcard / SNI SSL | TLS 1.2 & TLS 1.3 via Certbot |
| **Domain Publik** | `https://kampuslms-kelompok-06.proweb.my.id` | Port 80 auto-redirect 301 ke 443 |

---

## 🚀 2. Panduan Instalasi Server dari Nol (*Fresh Server Setup*)

Berikut adalah langkah-langkah terminal berurutan yang dijalankan oleh **Tika Mila Wahyuni** untuk menyiapkan server dari kondisi kosongan hingga siap melayani aplikasi:

### Langkah 1: Update Sistem & Instalasi Paket Inti
```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx git curl unzip supervisor certbot python3-certbot-nginx
```

### Langkah 2: Instalasi PHP 8.4 & Ekstensi Wajib Laravel
```bash
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.4-fpm php8.4-cli php8.4-mysql php8.4-curl php8.4-mbstring \
                    php8.4-xml php8.4-bcmath php8.4-zip php8.4-intl php8.4-tokenizer
```

### Langkah 3: Instalasi Composer 2 & Node.js 20
```bash
# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Node.js & NPM
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

### Langkah 4: Clone Repositori & Persiapan Direktori Aplikasi
```bash
sudo mkdir -p /var/www/kampuslms-kelompok-06
sudo git clone https://github.com/si2514024-pemrograman-web/kampuslms-kelompok-06.git /var/www/kampuslms-kelompok-06
cd /var/www/kampuslms-kelompok-06
```

### Langkah 5: Konfigurasi `.env` Produksi & Kunci Enkripsi
```bash
sudo cp .env.example .env
sudo nano .env
```
Isi konfigurasi produksi:
```env
APP_NAME=EduKampus
APP_ENV=production
APP_DEBUG=false
APP_URL=https://kampuslms-kelompok-06.proweb.my.id

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kampus_db
DB_USERNAME=kampus_user
DB_PASSWORD=PasswordDatabaseAman2026!

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
QUEUE_CONNECTION=database
CACHE_STORE=database
```

Jalankan perintah kunci:
```bash
sudo php artisan key:generate
```

### Langkah 6: Hak Akses Direktori Penyimpanan (*Permissions*)
```bash
sudo chown -R www-data:www-data /var/www/kampuslms-kelompok-06/storage /var/www/kampuslms-kelompok-06/bootstrap/cache
sudo chmod -R 775 /var/www/kampuslms-kelompok-06/storage /var/www/kampuslms-kelompok-06/bootstrap/cache
```

### Langkah 7: Kompilasi Aset Frontend & Migrasi Database Awal
```bash
composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run build
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

---

## 🔒 3. Konfigurasi Web Server Nginx (Ubuntu)

Berkas konfigurasi disimpan di `/etc/nginx/sites-available/kampuslms` dan disalin dari [`scripts/nginx-kampuslms.conf`](../scripts/nginx-kampuslms.conf):

```nginx
# 1. Redirect HTTP (Port 80) -> HTTPS (Port 443) Otomatis
server {
    listen 80;
    listen [::]:80;
    server_name kampuslms-kelompok-06.proweb.my.id kampuslms.my.id;

    return 301 https://$host$request_uri;
}

# 2. Server Blok Utama HTTPS (Port 443 SSL)
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name kampuslms-kelompok-06.proweb.my.id kampuslms.my.id;

    # KRITIS (Spesifikasi §12.1): Document root WAJIB mengarah ke /public!
    root /var/www/kampuslms-kelompok-06/public;

    ssl_certificate /etc/letsencrypt/live/kampuslms.my.id/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/kampuslms.my.id/privkey.pem;

    # Header Keamanan Produksi
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    index index.php index.html;
    charset utf-8;

    client_max_body_size 25M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    # Blokir akses ke seluruh berkas titik (.env, .git, dll)
    location ~ /\.(?!well-known).* {
        deny all;
        access_log off;
        log_not_found off;
    }

    # Blokir akses langsung ke storage logs dan konfigurasi dependensi
    location ~ ^/(storage/logs|composer\.(json|lock)|package\.(json|lock)|phpunit\.xml) {
        deny all;
        return 404;
    }
}
```

Aktifkan konfigurasi dan verifikasi sintaks:
```bash
sudo ln -s /etc/nginx/sites-available/kampuslms /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

---

## ⚡ 4. Konfigurasi Background Queue Worker (Supervisor)

Dikelola oleh **Shabrina Putri Azzahra**, berkas di `/etc/supervisor/conf.d/kampuslms-worker.conf`:

```ini
[program:kampuslms-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/kampuslms-kelompok-06/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/kampuslms-kelompok-06/storage/logs/worker.log
stopwaitsecs=3600
```

Perintah pengelolaan worker:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start kampuslms-worker:*
sudo supervisorctl status
```

---

## 🛡️ 5. Bukti Audit Keamanan Eksternal (Uji `curl -I`)

Dijalankan oleh **Raihandy Wijaya** dari mesin eksternal untuk membuktikan bahwa tidak ada berkas sensitif yang terekspos:

### 1. Uji File `.env`:
```bash
curl -I https://kampuslms-kelompok-06.proweb.my.id/.env
```
**Hasil Respons:**
```http
HTTP/2 403 Forbidden
server: nginx
date: Fri, 06 Nov 2026 14:10:00 GMT
content-type: text/html
content-length: 162
```
*(Status: **LOLOS** — Akses ditolak oleh aturan Nginx `location ~ /\.`)*

### 2. Uji File Log Laravel:
```bash
curl -I https://kampuslms-kelompok-06.proweb.my.id/storage/logs/laravel.log
```
**Hasil Respons:**
```http
HTTP/2 404 Not Found
server: nginx
```
*(Status: **LOLOS** — Direktori log berada di luar folder document root `public/`)*

### 3. Uji File `composer.json`:
```bash
curl -I https://kampuslms-kelompok-06.proweb.my.id/composer.json
```
**Hasil Respons:**
```http
HTTP/2 404 Not Found
server: nginx
```
*(Status: **LOLOS** — Tidak dapat diakses publik)*

---

## 🔄 6. Skrip Otomasi Deployment Ulang (`scripts/deploy.sh`)

Dijalankan oleh **Oktavia Nur Rahmadani** setiap kali ada pembaruan kode dari branch `main`:

```bash
cd /var/www/kampuslms-kelompok-06
bash scripts/deploy.sh
```

Tahapan otomatis:
1. `php artisan down` (Aktifkan maintenance mode)
2. `git fetch origin main && git reset --hard origin/main` (Tarik commit terbaru)
3. `composer install --no-dev --optimize-autoloader`
4. `npm ci && npm run build` (Kompilasi Vite produksi)
5. `php artisan migrate --force`
6. `php artisan config:cache && route:cache && view:cache && event:cache`
7. `php artisan storage:link`
8. `php artisan queue:restart` (Kritis: restart worker lama di RAM)
9. `php artisan up` (Aplikasi kembali melayani pengguna)

---

## 🔑 7. Akun Demo Evaluasi di Server Produksi

Untuk keperluan evaluasi penguji, akun demo diinisialisasi menggunakan perintah:
```bash
php artisan db:seed --class=DemoAccountSeeder --force
```

| Peran | Alamat Email | Kata Sandi Produksi | Deskripsi |
|---|---|---|---|
| **Administrator** | `admin@kampuslms.test` | `KampusLMS@Admin2026!` | Akses penuh sistem |
| **Dosen** | `dosen@kampuslms.test` | `KampusLMS@Dosen2026!` | Mengampu MK & Penilaian |
| **Mahasiswa** | `mahasiswa@kampuslms.test` | `KampusLMS@Mhs2026!` | Peserta perkuliahan |

> *Catatan Keamanan: Kata sandi produksi ini sengaja didokumentasikan di sini (dokumen deployment), dan **TIDAK** dipublikasikan di file `README.md` utama.*

---

## 📊 8. Pemantauan & Cara Membaca Log Server (*Log Monitoring*)

Jika terjadi kendala saat `APP_DEBUG=false`, jangan menyalakan debug di layar publik. Pantau berkas log secara langsung di server:

1. **Log Aplikasi Laravel (Error 500 & Exceptions):**
   ```bash
   tail -n 50 -f /var/www/kampuslms-kelompok-06/storage/logs/laravel.log
   ```
2. **Log Background Worker Antrean:**
   ```bash
   tail -n 50 -f /var/www/kampuslms-kelompok-06/storage/logs/worker.log
   ```
3. **Log Kesalahan Nginx:**
   ```bash
   tail -n 50 -f /var/log/nginx/error.log
   ```
4. **Log Layanan PHP-FPM:**
   ```bash
   tail -n 50 -f /var/log/php8.4-fpm.log
   ```

---

## 🛠️ 9. Log Kendala Deployment & Solusi (*Troubleshooting Log*)

Disusun oleh **Oktavia Nur Rahmadani** berdasarkan catatan kendala nyata selama proses deployment:

| No | Gejala Permasalahan | Akar Penyebab | Solusi Perbaikan |
|:---:|---|---|---|
| 1 | **Halaman 500 Blank saat pertama kali dibuka** | Web server `www-data` tidak memiliki hak tulis pada folder `storage/logs`. | Jalankan `sudo chown -R www-data:www-data storage bootstrap/cache` dan `chmod -R 775`. |
| 2 | **Tampilan CSS / Tailwind berantakan (rusak)** | Manifest Vite belum dikompilasi untuk mode produksi. | Jalankan `npm ci && npm run build` di server. |
| 3 | **Fungsi konfigurasi mengembalikan nilai `null`** | Kode memanggil `env()` setelah `config:cache` aktif. | Ubah seluruh pemanggilan `env()` di controller menjadi helper `config('...')`. |
| 4 | **Notifikasi tugas tidak terkirim** | Worker antrean belum di-restart setelah penarikan kode baru. | Jalankan `php artisan queue:restart` dan pastikan Supervisor aktif (`supervisorctl status`). |
