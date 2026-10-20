# Deployment Guide — KampusLMS (Kelompok 06)

> **Panduan Deployment & Otomasi CI/CD Menuju Lingkungan Produksi**  
> Sesuai Standar Modul 12 (Milestone M4: *Production Ready*) — Laravel 12 & Nginx/Ubuntu  

---

## 1. Arsitektur Pipeline CI/CD GitHub Actions

Aplikasi KampusLMS Kelompok 06 menerapkan siklus integrasi dan pengiriman berkelanjutan otomatis:

```text
[Developer Push / PR]
       │
       ▼
[GitHub Actions: CI (ci.yml)]
   ├── 1. Ensure .env is not committed
   ├── 2. Setup PHP 8.4 & Extensions
   ├── 3. Setup Node.js 20 & NPM Cache
   ├── 4. Composer Install & NPM Build (Vite)
   ├── 5. Composer Security Audit
   ├── 6. Database Migration & Rollback Test (SQLite)
   └── 7. Execute PHPUnit Tests
       │
       ▼ (Jika lulus & branch == 'main')
[GitHub Actions: CD (deploy.yml)]
   ├── 1. SSH ke Server VPS Target
   ├── 2. Eksekusi scripts/deploy.sh
   └── 3. Health Check Otomatis ke /up
```

---

## 2. Pengaturan GitHub Repository Secrets

Agar workflow Continuous Deployment (`.github/workflows/deploy.yml`) dapat terhubung secara aman ke server VPS, daftarkan secrets berikut di **GitHub Repository → Settings → Secrets and variables → Actions**:

| Secret Name | Deskripsi | Contoh Nilai |
|---|---|---|
| `SSH_HOST` | IP Address atau Hostname VPS | `103.187.12.34` atau `vps.kampuslms.my.id` |
| `SSH_USER` | Username SSH di VPS | `ubuntu` atau `deployer` |
| `SSH_PRIVATE_KEY` | SSH Private Key (ed25519/rsa) | `-----BEGIN OPENSSH PRIVATE KEY----- ...` |
| `SSH_PORT` *(Opsional)* | Port SSH server (default: 22) | `22` |
| `SERVER_DEPLOY_PATH` | Lokasi root direktori proyek di server | `/var/www/kampuslms-kelompok-06` |
| `APP_PRODUCTION_URL` | Domain publik aplikasi (untuk health check) | `https://kampuslms.my.id` |

---

## 3. Skrip Deployment Server (`scripts/deploy.sh`)

Skrip ini dieksekusi secara otomatis oleh GitHub Actions (atau dapat dijalankan manual oleh admin server):

```bash
# Menjalankan manual di server VPS:
cd /var/www/kampuslms-kelompok-06
bash scripts/deploy.sh
```

**Tahapan yang dikerjakan `deploy.sh`:**
1. Mengaktifkan maintenance mode (`php artisan down --retry=60`).
2. Menarik kode terverifikasi terbaru (`git fetch && git reset --hard origin/main`).
3. Memperbarui dependensi produksi (`composer install --no-dev --optimize-autoloader`).
4. Membangun aset frontend (`npm ci && npm run build`).
5. Menjalankan migrasi database aman (`php artisan migrate --force`).
6. Membangun cache performa (`config:cache`, `route:cache`, `view:cache`, `event:cache`).
7. Memastikan symlink storage publik (`php artisan storage:link`).
8. Me-restart queue worker Supervisor (`php artisan queue:restart`).
9. Mematikan maintenance mode (`php artisan up`).

---

## 4. Konfigurasi Web Server Nginx (Ubuntu)

Berkas konfigurasi di `/etc/nginx/sites-available/kampuslms`:

```nginx
server {
    listen 80;
    server_name kampuslms.my.id;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name kampuslms.my.id;
    root /var/www/kampuslms-kelompok-06/public;

    # SSL Certificates (Let's Encrypt / Certbot)
    ssl_certificate /etc/letsencrypt/live/kampuslms.my.id/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/kampuslms.my.id/privkey.pem;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    # Laravel Front Controller
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    # PHP-FPM Processor
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    # Blokir akses ke file sensitif
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

---

## 5. Konfigurasi Queue Worker (Supervisor)

Untuk menjalankan antrean asynchronous (seperti pengiriman email, notifikasi pengumpulan tugas Minggu 10):

Berkas `/etc/supervisor/conf.d/kampuslms-worker.conf`:

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

Muat ulang konfigurasi:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start kampuslms-worker:*
```

---

## 6. Akun Demo Produksi & Keamanan Pasca-Evaluasi

Akun-akun berikut tersedia untuk keperluan evaluasi penguji:

| Peran | Email | Kata Sandi Default |
|---|---|---|
| **Admin** | `admin@kampuslms.test` | `KampusLMS@Admin2025!` |
| **Dosen** | `dosen@kampuslms.test` | `KampusLMS@Dosen2025!` |
| **Mahasiswa** | `mahasiswa@kampuslms.test` | `KampusLMS@Mhs2025!` |

### Cara Update Kata Sandi Demo di Produksi:
```bash
# 1. Atur DEMO_PASSWORD di .env produksi
# 2. Jalankan seeder khusus
php artisan db:seed --class=DemoAccountSeeder --force
```

### Catatan Keamanan Produksi:
- Wajib memastikan `APP_DEBUG=false` di `.env`.
- Pastikan hak akses folder `storage` dan `bootstrap/cache` dimiliki oleh `www-data`:
  ```bash
  sudo chown -R www-data:www-data storage bootstrap/cache
  sudo chmod -R 775 storage bootstrap/cache
  ```
- Pasang SSL Let's Encrypt gratis via Certbot:
  ```bash
  sudo certbot --nginx -d kampuslms.my.id
  ```
