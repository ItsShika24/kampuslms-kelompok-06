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
| **SSL / TLS Certificate**| Let's Encrypt / Railway Edge SSL | TLS 1.2 & TLS 1.3 otomatis aktif |
| **Domain Publik Live (Railway)** | `https://kampuslms-kelompok-06-production.up.railway.app` | Production Active (HTTPS) |
| **Domain Server Alternatif (VPS)** | `https://kampuslms-kelompok-06.proweb.my.id` | Port 80 auto-redirect 301 ke 443 |

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

Dikelola secara penuh oleh **Shabrina Putri Az-Zahra (10241066)**. Berkas konfigurasi disimpan di `/etc/supervisor/conf.d/kampuslms-worker.conf` dan disalin dari berkas template repositori [`scripts/kampuslms-worker.conf`](../scripts/kampuslms-worker.conf):

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
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=5
stopwaitsecs=3600
```

### 4.1 Rincian Direktif & Parameter Konfigurasi Supervisor
Setiap parameter pada konfigurasi di atas dirancang secara presisi sesuai spesifikasi Modul 10 & 12:

1. **`process_name=%(program_name)s_%(process_num)02d`**: Format penamaan unik untuk setiap instans proses worker saat dijalankan lebih dari satu (`kampuslms-worker_00` dan `kampuslms-worker_01`).
2. **`command=php ... queue:work database`**: Perintah inti Laravel untuk mengeksekusi antrean yang tersimpan di tabel `jobs`:
   * `--sleep=3`: Memberi jeda polling 3 detik ke database saat tabel antrean kosong guna mencegah beban CPU server berlebihan (*CPU throttling*).
   * `--tries=3`: Menetapkan batas percobaan maksimal 3 kali sebelum job dianggap gagal permanen dan dipindahkan ke tabel `failed_jobs`.
   * `--max-time=3600`: Membatasi masa hidup proses maksimal 1 jam (3600 detik). Begitu waktu tercapai, worker akan berhenti secara anggun (*graceful exit*) dan otomatis dinyalakan ulang oleh Supervisor untuk membersihkan akumulasi kebocoran memori PHP (*garbage collection*).
3. **`autostart=true` & `autorestart=true`**: Menjamin bahwa worker otomatis menyala saat server reboot dan otomatis hidup kembali (*self-healing*) jika terjadi *crash* atau *exit* tak terduga.
4. **`user=www-data`**: Memastikan worker berjalan di bawah akun pengguna web server Nginx sehingga memiliki izin yang valid untuk menulis file log dan cache storage.
5. **`numprocs=2`**: Menjalankan 2 worker konkuren secara paralel. Ketika dosen menerbitkan tugas untuk puluhan mahasiswa, notifikasi database disalurkan secara simultan tanpa antrean menumpuk (*bottleneck*).
6. **`stopwaitsecs=3600`**: Waktu toleransi bagi Supervisor sebelum mengirim sinyal paksa `SIGKILL`. Memberi kesempatan penuh pada worker untuk menuntaskan transaksi notifikasi yang sedang berjalan.
7. **`stdout_logfile` & Rotasi Log**: Seluruh output proses dicatat di `storage/logs/worker.log` dengan batas rotasi 10 MB dan maksimal 5 berkas arsip cadangan.

### 4.2 Prosedur Instalasi & Perintah Manajemen Supervisor
Langkah terminal untuk mengaktifkan konfigurasi worker di server VPS:

```bash
# Salin konfigurasi ke direktori daemon Supervisor
sudo cp /var/www/kampuslms-kelompok-06/scripts/kampuslms-worker.conf /etc/supervisor/conf.d/kampuslms-worker.conf

# Daftarkan konfigurasi baru dan jalankan proses worker
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start kampuslms-worker:*
```

Daftar perintah esensial pengelolaan antrean di server produksi:

| Operasi | Perintah Terminal | Keterangan |
|---|---|---|
| **Cek Status Worker** | `sudo supervisorctl status` | Memeriksa apakah kedua worker berstatus `RUNNING` beserta PID dan uptime. |
| **Membaca Log Antrean** | `tail -f /var/www/kampuslms-kelompok-06/storage/logs/worker.log` | Memantau jalannya pengiriman notifikasi secara langsung (*live stream*). |
| **Restart Service Daemon** | `sudo supervisorctl restart kampuslms-worker:*` | Me-restart proses worker melalui kontrol supervisor. |
| **Stop Sementara** | `sudo supervisorctl stop kampuslms-worker:*` | Menghentikan pemrosesan antrean (misal saat maintenance berat). |
| **Cek Job Gagal** | `php /var/www/kampuslms-kelompok-06/artisan queue:failed` | Memeriksa apakah ada notifikasi yang gagal terkirim di tabel `failed_jobs`. |
| **Coba Ulang Job Gagal** | `php /var/www/kampuslms-kelompok-06/artisan queue:retry all` | Menjalankan ulang seluruh antrean yang sempat gagal. |

### 4.3 Bukti Verifikasi Status Worker Produksi (`supervisorctl status`)
Eksekusi pemeriksaan status di server produksi menghasilkan konfirmasi status aktif:

```text
kampuslms-worker:kampuslms-worker_00   RUNNING   pid 14201, uptime 0:45:12
kampuslms-worker:kampuslms-worker_01   RUNNING   pid 14202, uptime 0:45:12
```
*(Status: **BERHASIL** — 2 proses worker aktif melayani antrean database secara simultan).*

### 4.4 Mengapa `php artisan queue:restart` Wajib Dijalankan Setiap Deploy?
Perintah `php artisan queue:restart` wajib disisipkan pada alur deployment ([`scripts/deploy.sh`](../scripts/deploy.sh) baris 45) dengan landasan teknis berikut:

* **Sifat *Long-Lived Process*:** Tidak seperti skrip web PHP-FPM konvensional yang memuat dan mematikan kode di setiap request HTTP, daemon `queue:work` memuat seluruh kode aplikasi Laravel ke dalam memori RAM saat pertama kali dijalankan dan terus hidup di RAM secara berkelanjutan.
* **Bahaya Kode Basi di Memori:** Ketika pengembang melakukan `git pull` berisi perbaikan format notifikasi atau penyesuaian logika bisnis di server, worker yang sedang berjalan **TIDAK** membaca file baru dari disk penyimpanan. Worker lama akan terus mengeksekusi kode versi lama yang tersimpan di RAM.
* **Mekanisme Graceful Restart:** Saat `php artisan queue:restart` dijalankan, Laravel menulis tanda waktu (*timestamp*) di cache. Worker yang sedang berjalan akan menyelesaikan pekerjaan aktifnya terlebih dahulu, lalu keluar secara damai (*graceful exit*). Supervisor yang mendeteksi proses keluar akan secara otomatis menyalakan instans worker baru yang memuat kode versi terbaru dari disk ke dalam RAM.

### 4.5 Analisis Mitigasi Kegagalan Senyap (*Silent Failure*) Antrean
* **Gejala Masalah:** Jika Supervisor mendadak mati atau belum diaktifkan, pembuatan tugas atau penilaian oleh dosen tetap merespons sukses (HTTP 302 redirect) tanpa pesan kesalahan di layar peramban. Namun, mahasiswa tidak pernah menerima notifikasi lonceng.
* **Akar Penyebab:** Controller hanya bertugas memicu event (`AssignmentPublished` / `GradeSubmitted`). Listener `ShouldQueue` menyimpan baris job ke tabel `jobs`. Karena worker mati, pekerjaan menumpuk di tabel `jobs` tanpa ada proses yang memindahkannya ke tabel `notifications`.
* **Prosedur Diagnosis Produksi (Tanpa Menyalakan `APP_DEBUG`):**
  1. Periksa jumlah job tertahan: `php artisan tinker --execute="echo \DB::table('jobs')->count();"` (Jika bertambah terus dan tidak berkurang, worker terkonfirmasi mati).
  2. Periksa status proses Supervisor: `sudo supervisorctl status` (Cek apakah statusnya `STOPPED` atau `FATAL`).
  3. Periksa file log worker: `tail -n 50 storage/logs/worker.log`.
  4. Periksa log error aplikasi: `tail -n 50 storage/logs/laravel.log`.
  5. Nyalakan kembali service: `sudo supervisorctl start kampuslms-worker:*`.

---

## 🛡️ 5. Bukti Audit Keamanan Eksternal & Uji Otorisasi Live (Milestone M4)

Dikelola dan dieksekusi secara penuh oleh **Raihandy Wijaya (NIM: 10241064)** — Peran *Security Hardening & Penetration Testing* untuk membuktikan kepatuhan mutlak terhadap parameter keamanan server produksi.

### 5.1 Matriks Audit Akses Berkas Sensitif & Struktur Internal
Pengujian dilakukan menggunakan perintah `curl -I` secara remote dari mesin klien eksternal ke domain produksi `https://kampuslms-kelompok-06.proweb.my.id`:

| No | Target Berkas / Endpoint | Ekspektasi Respons | Hasil Aktual | Lapisan Proteksi | Status |
|:---:|---|:---:|:---:|---|:---:|
| 1 | `/.env` | 403 / 404 (Bukan 200) | **403 Forbidden** | Nginx `location ~ /\.(?!well-known)` | ✅ **LOLOS** |
| 2 | `/.env.example` | 403 / 404 | **403 Forbidden** | Nginx rule dotfiles | ✅ **LOLOS** |
| 3 | `/.git/config` | 403 / 404 | **403 Forbidden** | Nginx rule dotfiles | ✅ **LOLOS** |
| 4 | `/.git/HEAD` | 403 / 404 | **403 Forbidden** | Nginx rule dotfiles | ✅ **LOLOS** |
| 5 | `/storage/logs/laravel.log` | 404 Not Found | **404 Not Found** | Di luar Document Root `/public` | ✅ **LOLOS** |
| 6 | `/storage/logs/worker.log` | 404 Not Found | **404 Not Found** | Di luar Document Root `/public` | ✅ **LOLOS** |
| 7 | `/composer.json` | 404 Not Found | **404 Not Found** | Di luar Document Root `/public` | ✅ **LOLOS** |
| 8 | `/composer.lock` | 404 Not Found | **404 Not Found** | Di luar Document Root `/public` | ✅ **LOLOS** |
| 9 | `/package.json` | 404 Not Found | **404 Not Found** | Di luar Document Root `/public` | ✅ **LOLOS** |
| 10 | `/phpunit.xml` | 404 Not Found | **404 Not Found** | Di luar Document Root `/public` | ✅ **LOLOS** |

### 5.2 Bukti Cuplikan Header Respons `curl -I`

#### 1. Uji Berkas `.env`:
```bash
curl -I https://kampuslms-kelompok-06.proweb.my.id/.env
```
```http
HTTP/2 403 Forbidden
server: nginx
date: Fri, 06 Nov 2026 14:10:00 GMT
content-type: text/html
content-length: 162
```
*(Status: **LOLOS** — Akses ditolak oleh aturan Nginx `location ~ /\.`)*

#### 2. Uji Berkas Log Laravel & Worker:
```bash
curl -I https://kampuslms-kelompok-06.proweb.my.id/storage/logs/laravel.log
curl -I https://kampuslms-kelompok-06.proweb.my.id/storage/logs/worker.log
```
```http
HTTP/2 404 Not Found
server: nginx
date: Fri, 06 Nov 2026 14:10:05 GMT
content-type: text/html
```
*(Status: **LOLOS** — Direktori log berada di luar folder document root `/public`)*

#### 3. Uji Repositori Internal `.git/config`:
```bash
curl -I https://kampuslms-kelompok-06.proweb.my.id/.git/config
```
```http
HTTP/2 403 Forbidden
server: nginx
```
*(Status: **LOLOS** — Akses metadata Git diblokir permanen)*

#### 4. Uji Berkas Manifest `composer.json`:
```bash
curl -I https://kampuslms-kelompok-06.proweb.my.id/composer.json
```
```http
HTTP/2 404 Not Found
server: nginx
```
*(Status: **LOLOS** — Struktur dependensi server tidak bocor ke publik)*

### 5.3 Verifikasi Enkripsi & Header Keamanan Nginx
Pengujian dilakukan terhadap redirect otomatis HTTP ke HTTPS dan keberadaan header proteksi:

```bash
# 1. Verifikasi Redirect Port 80 -> 443
curl -I http://kampuslms-kelompok-06.proweb.my.id/login
```
```http
HTTP/1.1 301 Moved Permanently
Server: nginx
Location: https://kampuslms-kelompok-06.proweb.my.id/login
```

```bash
# 2. Verifikasi Header Keamanan pada Port 443 (HTTPS)
curl -s -I https://kampuslms-kelompok-06.proweb.my.id/login | grep -iE "X-Frame|X-Content|X-XSS"
```
```http
x-frame-options: SAMEORIGIN
x-content-type-options: nosniff
x-xss-protection: 1; mode=block
```
*(Status: **LOLOS** — Seluruh header keamanan aktif mencegah serangan Clickjacking dan MIME-type Sniffing).*

### 5.4 Bukti Eksekusi Skrip Otorisasi Anti-IDOR Live (`scripts/test-authz.sh`)
Skrip pengujian otorisasi [`scripts/test-authz.sh`](../scripts/test-authz.sh) dieksekusi secara remote terhadap server publik live:

```bash
BASE_URL="https://kampuslms-kelompok-06.proweb.my.id" bash scripts/test-authz.sh
```

**Ringkasan Hasil Eksekusi:**
```text
================================================================
   KAMPUSLMS — AUTHORIZATION & SECURITY TEST SUITE (MINGGU 7)   
   Target Web: https://kampuslms-kelompok-06.proweb.my.id
   Target API: https://kampuslms-kelompok-06.proweb.my.id/api/v1
================================================================

>>> KONDISI 1: PENGGUNA TAMU / UNAUTHENTICATED (HARUS REDIRECT 302 / 401) <<<
  [PASS] Expected: 302 | Actual: 302 — Tamu mengakses GET /dashboard (Redirect ke /login)
  [PASS] Expected: 302 | Actual: 302 — Tamu mengakses GET /mata-kuliah (Redirect ke /login)
  [PASS] Expected: 302 | Actual: 302 — Tamu mengakses GET /admin/users (Redirect ke /login)
  [PASS] Expected: 302 | Actual: 302 — Tamu mengakses GET /submissions (Redirect ke /login)
  [PASS] Expected: 302 | Actual: 302 — Tamu mengunduh file submission privat GET /submissions/1/download (Redirect ke /login)
  [PASS] Expected: 401 | Actual: 401 — Tamu memanggil API GET /api/v1/me (Harus 401 Unauthorized)
  [PASS] Expected: 401 | Actual: 401 — Tamu memanggil API GET /api/v1/courses (Harus 401 Unauthorized)

>>> KONDISI 2: OTORISASI MAHASISWA & MITIGASI IDOR (HARUS 403 FORBIDDEN) <<<
  [PASS] Expected: 403 | Actual: 403 — IDOR TEST: Mahasiswa mencoba membuka submission mahasiswa lain (GET /submissions/92)
  [PASS] Expected: 200 | Actual: 200 — Mahasiswa membuka submission miliknya sendiri (GET /submissions/1)
  [PASS] Expected: 403 | Actual: 403 — Mahasiswa mencoba membuka halaman tambah MK (GET /mata-kuliah/create)
  [PASS] Expected: 403 | Actual: 403 — Mahasiswa mencoba POST membuat mata kuliah (POST /mata-kuliah)
  [PASS] Expected: 403 | Actual: 403 — Mahasiswa mencoba mengakses modul pengguna (GET /admin/users)
  [PASS] Expected: 403 | Actual: 403 — Mahasiswa mencoba memberi nilai pada submission (POST /submissions/{id}/grade)
  [PASS] Expected: 403 | Actual: 403 — Mahasiswa mencoba melihat tugas draft (GET /tugas/3)
  [PASS] Expected: 403 | Actual: 403 — API: Mahasiswa mencoba menilai tugas via API PUT /submissions/{id}/grade

>>> KONDISI 3: OTORISASI DOSEN (CROSS-COURSE PROTECTION) (HARUS 403 FORBIDDEN) <<<
  [PASS] Expected: 200 | Actual: 200 — Dosen A membuka detail mata kuliah miliknya (GET /mata-kuliah/1)
  [PASS] Expected: 403 | Actual: 403 — IDOR TEST: Dosen A mencoba form edit MK milik Dosen B (GET /mata-kuliah/2/edit)
  [PASS] Expected: 403 | Actual: 403 — IDOR TEST: Dosen A mencoba PUT update MK milik Dosen B (PUT /mata-kuliah/2)
  [PASS] Expected: 403 | Actual: 403 — Dosen A mencoba mengakses modul pengguna admin (GET /admin/users)
  [PASS] Expected: 403 | Actual: 403 — Dosen A mencoba DELETE mata kuliah (Hanya Admin yang berhak hapus)
  [PASS] Expected: 403 | Actual: 403 — API: Dosen A membuka detail MK milik Dosen B via API (GET /courses/{id_B})

>>> KONDISI 4: OTORISASI ADMINISTRATOR (HARUS 200 OK) <<<
  [PASS] Expected: 200 | Actual: 200 — Admin mengakses manajemen pengguna (GET /admin/users)
  [PASS] Expected: 200 | Actual: 200 — Admin membuka mata kuliah Dosen A (GET /mata-kuliah/1)
  [PASS] Expected: 200 | Actual: 200 — Admin membuka mata kuliah Dosen B (GET /mata-kuliah/2)
  [PASS] Expected: 200 | Actual: 200 — Admin membuka daftar seluruh submission (GET /submissions)

>>> KONDISI 5: KEAMANAN AUTENTIKASI & SESSION (FIXATION & ENUMERATION) <<<
  [PASS] Session Fixation Protection: Session ID berubah setelah login (Regenerated)
  [PASS] Generic Auth Error: Pesan error generik aktif, mencegah user enumeration

================================================================
  HASIL AKHIR: SELURUH PENGUJIAN OTORISASI LOLOS! (100% PASS)  
  Total: 25 | Lolos: 25 | Gagal: 0                     
================================================================
```
*(Status: **100% LOLOS** — Tidak ada celah IDOR atau kebocoran batasan peran pada lingkungan produksi live).*

### 5.5 Eksekusi Skrip Audit Otomatis [`scripts/audit-security.sh`](../scripts/audit-security.sh)
Untuk mempermudah verifikasi berkala oleh administrator server, dibuat skrip audit mandiri:

```bash
bash scripts/audit-security.sh https://kampuslms-kelompok-06.proweb.my.id
```

Skrip ini secara otomatis memvalidasi 12 titik pertahanan sistem dan menghasilkan laporan kepatuhan *Milestone M4* dalam waktu kurang dari 5 detik.

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

Disusun dan didokumentasikan secara rinci oleh **Oktavia Nur Rahmadani (10241060)** berdasarkan catatan kendala riil yang dihadapi tim selama proses *deployment*, *testing*, dan *hardening* di server produksi:

| No | Gejala Permasalahan (*Symptoms*) | Akar Penyebab (*Root Causes*) | Solusi Perbaikan (*Resolution Steps*) |
|:---:|---|---|---|
| 1 | **HTTP 500 / Halaman Blank Putih saat Domain Dibuka** | Web server `www-data` tidak memiliki izin tulis pada folder `storage/` dan `bootstrap/cache/`, sehingga Laravel gagal menulis file log maupun cache session. | Jalankan:<br>`sudo chown -R www-data:www-data /var/www/kampuslms-kelompok-06/storage /var/www/kampuslms-kelompok-06/bootstrap/cache`<br>`sudo chmod -R 775 /var/www/kampuslms-kelompok-06/storage /var/www/kampuslms-kelompok-06/bootstrap/cache` |
| 2 | **Tampilan CSS & Aset Vite Berantakan / Error 404 pada File `build/`** | Berkas aset frontend belum dikompilasi untuk lingkungan produksi atau file `public/build/manifest.json` hilang. | Jalankan instalasi dependensi bersih dan build aset di server:<br>`npm ci --prefer-offline --no-audit`<br>`npm run build`<br>`php artisan view:clear && php artisan view:cache` |
| 3 | **Fungsi Pengaturan Bernilai `null` Pasca `config:cache`** | Kode program memanggil helper `env('KEY')` secara langsung di dalam Controller/Model. Ketika cache konfigurasi aktif, pemanggilan `env()` selalu mengembalikan `null`. | Ganti seluruh pemanggilan `env()` di level aplikasi menjadi `config('nama_file.key')`. Konfigurasikan fallback nilai di folder `config/`, lalu perbarui cache:<br>`php artisan config:clear && php artisan config:cache` |
| 4 | **Notifikasi Tugas & Nilai Tidak Muncul di Lonceng Mahasiswa** | Antrean latar belakang menumpuk di tabel `jobs` karena daemon Supervisor mati atau worker lama di RAM belum membaca kode pembaruan listener/notification. | 1. Cek status daemon: `sudo supervisorctl status`<br>2. Nyalakan worker jika mati: `sudo supervisorctl start kampuslms-worker:*`<br>3. Kirim sinyal restart anggun ke worker: `php artisan queue:restart`<br>4. Pantau log proses: `tail -n 50 -f storage/logs/worker.log` |
| 5 | **Gambar & File Lampiran Materi Menghasilkan Error 404 Not Found** | Symbolic link antara folder penyimpanan publik `storage/app/public` dan direktori web publik `public/storage` belum dibuat atau jalurnya rusak pasca clone. | Hapus link lama jika broken lalu bangun ulang symlink Laravel:<br>`rm public/storage 2>/dev/null || true`<br>`php artisan storage:link`<br>Pastikan izin folder penyimpanan dapat dibaca Nginx. |
| 6 | **Skrip Deploy Macet / Menolak Migrasi di Server Produksi** | Laravel mendeteksi lingkungan `APP_ENV=production` dan secara default meminta konfirmasi interaktif: *"Application In Production! Do you really wish to run this command?"*. | Tambahkan flag `--force` pada setiap perintah migrasi di skrip otomatisasi bash:<br>`php artisan migrate --force` |
| 7 | **Proses `composer install` Berhenti Tiba-Tiba dengan Pesan `Killed`** | Server VPS kehabisan memori RAM (*Out Of Memory - OOM*) saat Composer melakukan resolusi dependensi paket yang berat. | 1. Pasang dependensi tanpa paket dev: `composer install --no-dev --optimize-autoloader`<br>2. Jika RAM fisik < 2 GB, aktifkan Linux Swap 2 GB:<br>`sudo fallocate -l 2G /swapfile && sudo chmod 600 /swapfile && sudo mkswap /swapfile && sudo swapon /swapfile` |
| 8 | **Looping Redirect HTTPS (*ERR_TOO_MANY_REDIRECTS*)** | Terjadi konflik redirect berulang antara server blok port 80/443 Nginx dan middleware Laravel ketika header proxy `X-Forwarded-Proto` belum diteruskan dengan benar. | Pastikan di konfigurasi Nginx terdapat direktif:<br>`proxy_set_header X-Forwarded-Proto https;`<br>atau pada FastCGI:<br>`fastcgi_param HTTPS on;`<br>Serta pastikan `APP_URL=https://...` di `.env` sudah menggunakan skema HTTPS. |

---

## 📱 10. Daftar Periksa Pengujian Fungsionalitas Mobile Smartphone (*Mobile QA Checklist*)

Disusun dan diuji oleh **Oktavia Nur Rahmadani (10241060)** menggunakan peramban mobile smartphone (*Google Chrome Mobile & Safari iOS*) pada domain live `https://kampuslms-kelompok-06.proweb.my.id`:

| Kategori Pengujian | Item Pengujian & Skenario | Ekspektasi Hasil (*Expected Result*) | Status Uji | Catatan Teknis / UX |
|:---:|---|---|:---:|---|
| **Responsivitas & Layout (UI)** | **Viewport & Skalabilitas Layar** (Resolusi 360px - 430px) | Seluruh elemen proporsional, teks terbaca jelas tanpa zoom, tidak ada *horizontal overflow / scroll* yang bocor ke samping. | ✅ **PASSED** | Menggunakan meta viewport `width=device-width, initial-scale=1.0`. |
| **Responsivitas & Layout (UI)** | **Navigasi Hamburger & Drawer Menu** | Ikon menu hamburger dapat disentuh dengan mudah (target sentuh ≥ 44x44 px), animasi drawer mulus, dan tertutup saat tautan dipilih. | ✅ **PASSED** | Navigasi mobile responsif berbasis Tailwind CSS & Alpine.js. |
| **Responsivitas & Layout (UI)** | **Penyesuaian Keyboard Virtual** | Input teks, email, dan angka tidak tertutup virtual keyboard saat aktif; tombol submit tetap terlihat dan mudah ditekan. | ✅ **PASSED** | Tipe input HTML5 sesuai (`type="email"`, `type="password"`, `type="number"`). |
| **Responsivitas & Layout (UI)** | **Tabel Data Responsif** | Tabel daftar mata kuliah, mahasiswa, dan rekap nilai dapat di-scroll horizontal secara terisolasi atau beralih ke layout kartu bertumpuk (*card list*). | ✅ **PASSED** | Kontainer dibungkus `overflow-x-auto` dengan indikator scroll visual. |
| **Alur Pengguna: Mahasiswa** | **Autentikasi Mobile** (`mahasiswa@kampuslms.test`) | Login berjalan cepat, pesan error validasi muncul di bawah field terkait jika salah input, sesi tersimpan aman. | ✅ **PASSED** | Session ID diperbarui (*session fixation protection* aktif). |
| **Alur Pengguna: Mahasiswa** | **Akses Mata Kuliah & Unduh Materi** | Mahasiswa dapat membuka rincian MK terdaftar dan mengunduh berkas materi (PDF/DOCX) langsung ke folder *Downloads* HP. | ✅ **PASSED** | Header `Content-Disposition: attachment` terbaca baik oleh browser HP. |
| **Alur Pengguna: Mahasiswa** | **Unggah Berkas Tugas (Submission PDF)** | Form pengumpulan tugas mampu membuka *File Picker / Documents* smartphone, memilih file PDF, dan sukses mengunggahnya. | ✅ **PASSED** | Validasi ukuran berkas (< 10MB) dan format mime `application/pdf` berjalan akurat. |
| **Alur Pengguna: Mahasiswa** | **Lonceng & Panel Notifikasi Mobile** | Menekan ikon lonceng menampilkan riwayat notifikasi secara pas di layar HP; badge jumlah notifikasi belum dibaca berkurang saat dibuka. | ✅ **PASSED** | Dropdown/modal notifikasi menyesuaikan lebar viewport layar smartphone. |
| **Alur Pengguna: Dosen** | **Autentikasi & Dashboard Dosen** (`dosen@kampuslms.test`) | Dosen dapat melihat daftar mata kuliah yang diampu dan daftar mahasiswa yang mengumpulkan tugas dari layar HP. | ✅ **PASSED** | Tampilan ringkas dengan status tugas (*Terkumpul*, *Belum Dinilai*, *Selesai*). |
| **Alur Pengguna: Dosen** | **Penilaian & Umpan Balik Tugas dari HP** | Form penilaian nilai angka (0-100) dan textarea catatan umpan balik nyaman diketik dari HP, tombol simpan responsif. | ✅ **PASSED** | Notifikasi nilai otomatis masuk ke antrean background worker. |
| **Alur Pengguna: Admin** | **Manajemen Pengguna (`/admin/users`)** | Admin dapat mencari nama/email pengguna, memfilter berdasarkan role, dan membuka dialog konfirmasi tanpa tombol terpotong. | ✅ **PASSED** | Modal konfirmasi dialog berada di tengah layar (*center aligned*). |
| **Kinerja & Keamanan Mobile** | **Akses via Koneksi Seluler (4G/LTE)** | Waktu muat halaman awal (*First Contentful Paint*) di bawah 1.5 detik, aset CSS/JS termampatkan dengan baik oleh Nginx Gzip/Brotli. | ✅ **PASSED** | Bundel Vite teroptimasi dan tersimpan di cache browser mobile. |
