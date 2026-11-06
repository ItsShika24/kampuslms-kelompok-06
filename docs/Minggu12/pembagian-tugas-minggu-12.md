# PANDUAN & PEMBAGIAN TUGAS MINGGU 12 — DEPLOYMENT & PRODUCTION READY
## Proyek: KampusLMS (EduKampus) — Laravel 12
**Mata Kuliah:** SI2514024 Pemrograman Web (3 SKS)  
**Kelompok:** Kelompok 06  
**Target Akhir:** Aplikasi KampusLMS live di domain publik dengan HTTPS, queue worker berjalan via Supervisor, seluruh audit keamanan produksi lolos, dan dokumentasi lengkap di `docs/deployment.md`.

> ⚠️ **PERHATIAN SANGAT PENTING (MILESTONE M4 — TUGAS 4: BOBOT 7%):**  
> Minggu ini adalah **Milestone M4** (terakhir sebelum UAS). Dosen penguji menerapkan **DISKUALIFIKASI OTOMATIS (Nilai 0)** jika ditemukan salah satu dari 3 hal berikut di server produksi:  
> 1. `APP_DEBUG=true` di server publik.  
> 2. File `.env` bisa diunduh atau diakses publik (`curl -I https://domain/.env` menghasilkan HTTP 200).  
> 3. Berkas *submission* tugas mahasiswa tersimpan di folder publik atau bisa diunduh tanpa login.  
>
> Setiap anggota wajib memiliki commit atas namanya sendiri di GitHub melalui Pull Request (PR) sesuai bagian tugas masing-masing, serta membuat **rekaman layar individu 3–4 menit** untuk interview.

---

## 👥 Ringkasan Pembagian Peran Anggota

| No | Anggota | NIM | Fokus Tanggung Jawab Utama | Deliverable Kunci |
|:---:|---|:---:|---|---|
| 1 | **Tika Mila Wahyuni** | 10241070 | **Server Setup, Nginx, SSL, Clone & Initial Build** | Konfigurasi VPS, Nginx (`root /public`), SSL Let's Encrypt Certbot, setup `.env` produksi, migrasi & build awal, website menyala live. |
| 2 | **Shabrina Putri Azzahra** | 10241066 | **Queue Worker, Supervisor & Asinkron** | Konfigurasi Supervisor daemon worker, auto-start antrean database, uji `queue:restart`, screenshot status worker. |
| 3 | **Raihandy Wijaya** | 10241064 | **Security Audit, Uji Otorisasi Live & Hardening** | Eksekusi `curl -I` audit pada `.env`, verifikasi `APP_DEBUG=false`, uji `test-authz.sh` ke domain live, verifikasi anti-IDOR download. |
| 4 | **Oktavia Nur Rahmadani** | 10241060 | **Seeder Demo, Uji Otomasi Deploy, Mobile Test & Troubleshooting** | Eksekusi `DemoAccountSeeder`, pengujian skrip `scripts/deploy.sh`, uji akses via HP (mobile), menyusun log kendala & solusi di `docs/deployment.md`. |

---

## 📌 Rincian Tugas, File Konteks, dan Prompt AI per Anggota

---

### 1. Tika Mila Wahyuni (Server Infrastructure, Nginx, SSL & Initial Build)
**Peran:** Server Infrastructure & Web Server  
**Branch Git Rekomendasi:** `feat/deploy-server-nginx-tika`

#### A. Tugas Spesifik (Tahap 1 — Membuka Jalur Server):
1. **Setup Awal Server VPS & Penarikan Repositori:**
   - Lakukan penyiapan paket server di VPS Ubuntu: PHP 8.4/8.3, Nginx, MySQL/MariaDB, Composer, Git, dan Node.js 20.
   - Clone repositori kelompok ke direktori server:
     ```bash
     git clone https://github.com/org-anda/kampuslms-kelompok-06.git /var/www/kampuslms-kelompok-06
     cd /var/www/kampuslms-kelompok-06
     ```
   - Siapkan berkas `.env` produksi: salin dari `.env.example`, atur `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, generate app key (`php artisan key:generate`).
   - Jalankan instalasi dependensi, migrasi awal, dan build aset:
     ```bash
     composer install --no-dev --optimize-autoloader
     npm ci && npm run build
     php artisan migrate --force
     php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache
     php artisan storage:link
     ```
2. **Konfigurasi Web Server Nginx (Ubuntu):**
   - Buat virtual host `/etc/nginx/sites-available/kampuslms` (atau konfigurasikan akun server kelas).
   - **Kritis:** Pastikan direktori `root` mengarah tepat ke `/var/www/kampuslms-kelompok-06/public`, **BUKAN** ke root repositori!
   - Pasang aturan pemblokiran berkas titik/tersembunyi:
     ```nginx
     location ~ /\.(?!well-known).* {
         deny all;
     }
     ```
   - Tambahkan header keamanan wajib:
     ```nginx
     add_header X-Frame-Options "SAMEORIGIN";
     add_header X-Content-Type-Options "nosniff";
     ```
3. **Pemasangan Sertifikat SSL (HTTPS):**
   - Pasang SSL Let's Encrypt gratis via Certbot:
     ```bash
     sudo certbot --nginx -d kampuslms-kelompok-06.domain-anda.com
     ```
   - Pastikan rute HTTP (port 80) otomatis ter-redirect 301 ke HTTPS (port 443).
4. **Hak Akses & Izin Direktori Server:**
   - Atur kepemilikan direktori agar web server (`www-data`) dapat menulis file cache dan upload:
     ```bash
     sudo chown -R www-data:www-data storage bootstrap/cache
     sudo chmod -R 775 storage bootstrap/cache
     ```
5. **Dokumentasi Spesifikasi Server di `docs/deployment.md`:**
   - Tuliskan tabel spesifikasi VPS/server: Sistem Operasi, Versi PHP (8.3/8.4), Versi MySQL/MariaDB, Versi Nginx, dan arsitektur server.

#### B. File Repositori yang Diberikan ke AI Sebagai Konteks:
Lampirkan file-file berikut saat meminta bantuan ke AI:
* `docs/deployment.md`
* `.env.example`
* `public/index.php`

#### C. Prompt AI Siap Pakai untuk Tika:

```text
Halo, saya Tika Mila Wahyuni dari Kelompok 06 tugas Pemrograman Web (Laravel 12).
Tugas saya di Minggu 12 adalah memimpin setup infrastruktur server VPS Ubuntu dari nol, menarik repositori, mengonfigurasi Web Server Nginx dengan SSL (HTTPS), dan memastikan website online pertama kali.

Berikut file konfigurasi deployment awal kami:
[Lampirkan isi file: docs/deployment.md dan .env.example]

Tolong bantu saya:
1. Susun perintah terminal berurutan untuk setup server VPS Ubuntu (PHP 8.4, Composer, Git, Node.js), clone repo, konfigurasi .env produksi, dan build awal.
2. Buatkan blok konfigurasi Nginx (/etc/nginx/sites-available/kampuslms) yang aman untuk Laravel 12 dengan PHP-FPM.
3. Jelaskan mengapa baris 'root /var/www/kampuslms-kelompok-06/public;' sangat krusial, dan apa yang akan bocor ke publik jika kata '/public' dihilangkan.
4. Berikan langkah-langkah terminal berurutan untuk memasang Certbot Let's Encrypt dan memastikan redirect HTTP ke HTTPS berjalan otomatis.
5. Tunjukkan perintah chmod dan chown yang tepat untuk direktori storage/ dan bootstrap/cache/, serta jelaskan risiko keamanan jika pengembang sembarangan memakai 'chmod 777'.
```

---

### 2. Shabrina Putri Azzahra (Supervisor, Background Queue & Notifikasi)
**Peran:** Asynchronous Systems & Background Worker  
**Branch Git Rekomendasi:** `feat/supervisor-queue-shabrina`

#### A. Tugas Spesifik:
1. **Konfigurasi Supervisor Daemon di Server:**
   - Buat berkas `/etc/supervisor/conf.d/kampuslms-worker.conf`:
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
   - Muat dan jalankan service Supervisor:
     ```bash
     sudo supervisorctl reread
     sudo supervisorctl update
     sudo supervisorctl start kampuslms-worker:*
     ```
2. **Verifikasi dan Pengujian Queue di Lingkungan Produksi:**
   - Jalankan `sudo supervisorctl status` dan pastikan kedua proses worker berstatus `RUNNING`.
   - Ambil tangkapan layar (*screenshot*) keluaran `supervisorctl status` untuk dilampirkan pada laporan.
   - Buat satu tugas baru sebagai Dosen di server live, lalu periksa apakah notifikasi terkirim ke mahasiswa terdaftar secara otomatis di background tanpa membuat request web dosen menjadi lambat (*freeze*).
3. **Prosedur Restart Worker Pasca-Deploy:**
   - Dokumentasikan di `docs/deployment.md` mengapa perintah `php artisan queue:restart` wajib dieksekusi setiap kali ada pembaruan kode di server.
   - Periksa file log worker di `storage/logs/worker.log` untuk memastikan tidak ada job yang gagal (*failed jobs*).

#### B. File Repositori yang Diberikan ke AI Sebagai Konteks:
Lampirkan file-file berikut saat meminta bantuan ke AI:
* `app/Listeners/SendAssignmentNotification.php`
* `app/Listeners/SendGradeNotification.php`
* `app/Notifications/NewAssignmentNotification.php`
* `docs/deployment.md`

#### C. Prompt AI Siap Pakai untuk Shabrina:

```text
Halo, saya Shabrina Putri Azzahra dari Kelompok 06 tugas Pemrograman Web (Laravel 12).
Tugas saya di Minggu 12 adalah mengonfigurasi Supervisor daemon pada server Ubuntu untuk menjalankan queue worker Laravel (database queue) secara otomatis dan tangguh.

Berikut adalah listener dan arsitektur notifikasi antrean kami:
[Lampirkan isi file: app/Listeners/SendAssignmentNotification.php dan docs/deployment.md]

Tolong bantu saya:
1. Susun konfigurasi lengkap berkas /etc/supervisor/conf.d/kampuslms-worker.conf dengan 2 worker processes (numprocs=2) di bawah user www-data.
2. Tuliskan perintah terminal untuk reload, update, start, restart, dan cek status worker via supervisorctl.
3. Jelaskan apa yang terjadi jika aplikasi diperbarui dengan kode baru di server tetapi perintah 'php artisan queue:restart' lupa dijalankan.
4. Apa yang terjadi pada antrean notifikasi jika service Supervisor mendadak mati di server produksi? Bagaimana cara mendiagnosis masalah tersebut jika aplikasi web tidak menampilkan error apa pun?
```

---

### 3. Raihandy Wijaya (Security Audit, Verification & Hardening)
**Peran:** Security Hardening & Penetration Testing  
**Branch Git Rekomendasi:** `feat/security-audit-production-raihandy`

#### A. Tugas Spesifik:
1. **Verifikasi File Konfigurasi Produksi (`.env` Server):**
   - Pastikan parameter berikut aktif di `.env` server:
     ```env
     APP_ENV=production
     APP_DEBUG=false
     SESSION_SECURE_COOKIE=true
     ```
   - Pastikan `APP_KEY` server unik dan digenerate langsung di server (`php artisan key:generate`).
2. **Audit Akses Berkas Sensitif Eksternal (Uji curl):**
   - Dari terminal lokal / komputer luar, jalankan pengujian akses terhadap URL produksi:
     ```bash
     curl -I https://domain-anda.com/.env
     curl -I https://domain-anda.com/storage/logs/laravel.log
     curl -I https://domain-anda.com/composer.json
     ```
   - **Target:** Seluruh respons wajib **404 Not Found** atau **403 Forbidden**. Catat header output curl dan masukkan ke `docs/deployment.md`.
3. **Eksekusi Pengujian Otorisasi Live (`scripts/test-authz.sh`):**
   - Ubah variabel `BASE_URL` pada skrip `scripts/test-authz.sh` menjadi domain publik live Anda:
     ```bash
     BASE_URL="https://domain-anda.com" bash scripts/test-authz.sh
     ```
   - Pastikan seluruh pengujian otorisasi lolos (tidak ada celah IDOR di server produksi). Simpan log eksekusinya.
4. **Verifikasi Keamanan Unduhan Berkas Privat (Minggu 9):**
   - Coba akses URL berkas tugas secara langsung di browser tanpa login (`jendela incognito`). Pastikan ditolak dengan redirect ke login atau HTTP 401/403.

#### B. File Repositori yang Diberikan ke AI Sebagai Konteks:
Lampirkan file-file berikut saat meminta bantuan ke AI:
* `scripts/test-authz.sh`
* `docs/keamanan.md`
* `docs/deployment.md`
* `.env.example`

#### C. Prompt AI Siap Pakai untuk Raihandy:

```text
Halo, saya Raihandy Wijaya dari Kelompok 06 tugas Pemrograman Web (Laravel 12).
Tugas saya di Minggu 12 adalah memimpin Audit Keamanan Produksi, memverifikasi tidak ada berkas sensitif yang bocor, dan menjalankan skrip uji otorisasi anti-IDOR di domain live.

Berikut adalah dokumen keamanan dan skrip pengujian kami:
[Lampirkan isi file: scripts/test-authz.sh dan docs/deployment.md]

Tolong bantu saya:
1. Buatkan skrip terminal bash / daftar perintah curl lengkap untuk mengaudit keamanan server produksi (memeriksa .env, storage log, git, vendor, dan header HTTPS).
2. Tunjukkan cara menyesuaikan skrip scripts/test-authz.sh agar dapat dijalankan secara remote terhadap server publik live dengan HTTPS.
3. Jelaskan risiko nyata jika APP_DEBUG=true tetap aktif di server produksi, serta informasi spesifik apa saja yang bocor ke penyerang saat memicu error form.
4. Bagaimana cara memastikan cookie session Laravel hanya dikirim lewat jalur HTTPS (SESSION_SECURE_COOKIE), dan apa yang terjadi jika pengguna mencoba membukanya lewat HTTP biasa?
```

---

### 4. Oktavia Nur Rahmadani (Seeder Demo, Otomasi Deploy, Mobile Test & Troubleshooting)
**Peran:** Release Engineering & Quality Assurance  
**Branch Git Rekomendasi:** `feat/deploy-automation-qa-oktavia`

> 💡 **Urutan Pengerjaan:** Oktavia bekerja pada tahap **paling akhir** setelah server live, Nginx & SSL beres (Tika), queue Supervisor berjalan (Shabrina), dan audit keamanan eksternal lolos (Raihandy).

#### A. Tugas Spesifik (Tahap 4 — Finalisasi & Quality Assurance):
1. **Inisialisasi Akun Demo Produksi (`DemoAccountSeeder`):**
   - Setelah server aktif dan diaudit aman, jalankan seeder khusus akun demo di server:
     ```bash
     php artisan db:seed --class=DemoAccountSeeder --force
     ```
   - Catat kata sandi akun demo produksi (Admin, Dosen, Mahasiswa) di `docs/deployment.md` (dan pastikan kata sandi produksi **TIDAK** ditulis di `README.md` publik).
2. **Pengujian dan Eksekusi Skrip Otomasi [`scripts/deploy.sh`](../../scripts/deploy.sh):**
   - Uji coba eksekusi skrip deployment di server VPS untuk membuktikan otomasi pembaruan kode berjalan lancar:
     ```bash
     bash scripts/deploy.sh
     ```
   - Pastikan urutan perintah berjalan tanpa error: `php artisan down` → `git pull` → `composer install --no-dev` → `npm run build` → `migrate --force` → `cache build` → `storage:link` → `queue:restart` → `php artisan up`.
3. **Pengujian End-to-End dari Perangkat Mobile (Smartphone):**
   - Buka domain publik aplikasi melalui browser HP anggota tim.
   - Lakukan uji fungsionalitas dari HP:
     - Login Mahasiswa → lihat mata kuliah → unduh materi perkuliahan.
     - Upload tugas format PDF dari galeri/file manager HP.
     - Login Dosen → beri nilai dan umpan balik tugas.
   - Ambil tangkapan layar tampilan antarmuka di HP untuk bukti laporan.
4. **Menyusun Troubleshooting Log di `docs/deployment.md`:**
   - Kumpulkan catatan kendala yang dialami teman-teman (Tika, Shabrina, Raihandy) dan catat minimal 2–3 kendala nyata yang dihadapi tim saat proses deployment (misal: *permission denied storage*, aset Vite blank sebelum `npm run build`, atau *error 500* karena koneksi DB) beserta solusi perbaikannya.

#### B. File Repositori yang Diberikan ke AI Sebagai Konteks:
Lampirkan file-file berikut saat meminta bantuan ke AI:
* `scripts/deploy.sh`
* `database/seeders/DemoAccountSeeder.php`
* `docs/deployment.md`
* `README.md`

#### C. Prompt AI Siap Pakai untuk Oktavia:

```text
Halo, saya Oktavia Nur Rahmadani dari Kelompok 06 tugas Pemrograman Web (Laravel 12).
Tugas saya di Minggu 12 adalah pada tahap akhir: menyiapkan akun demo produksi via DemoAccountSeeder, menguji otomasi skrip deploy.sh di server, menguji UX aplikasi dari smartphone, dan menyusun log troubleshooting di docs/deployment.md.

Berikut adalah skrip deploy dan seeder kami:
[Lampirkan isi file: scripts/deploy.sh, DemoAccountSeeder.php, dan docs/deployment.md]

Tolong bantu saya:
1. Periksa skrip scripts/deploy.sh yang kami miliki: apakah urutannya sudah benar, dan jelaskan mengapa flag 'set -e' wajib dipasang di awal skrip bash.
2. Jelaskan mengapa kata sandi akun demo produksi wajib disimpan di docs/deployment.md dan DILARANG dicantumkan di README.md publik repositori.
3. Bantu saya menyusun format tabel 'Troubleshooting & Kendala Deployment' untuk docs/deployment.md yang menjelaskan gejala masalah, akar penyebab, dan solusi perbaikannya berdasarkan kendala umum server Laravel.
4. Susun daftar periksa (checklist) pengujian fungsionalitas aplikasi KampusLMS saat diakses dari peramban mobile smartphone.
```

---

## 📅 Alur & Urutan Pengerjaan Bersama (Checklist Minggu 12)

Urutan pengerjaan tim dirancang sekuensial **Tika → Shabrina → Raihandy → Oktavia**:

```
[Tahap 1: Tika]     ──> Setup VPS, instalasi Nginx, pasang SSL Certbot, clone repo, .env produksi & website online
       │
[Tahap 2: Shabrina] ──> Konfigurasi Supervisor, jalankan 2 background queue workers & uji notifikasi antrean
       │
[Tahap 3: Raihandy] ──> Audit keamanan luar (curl -I pada .env), jalankan scripts/test-authz.sh di domain live
       │
[Tahap 4: Oktavia]  ──> Eksekusi DemoAccountSeeder, uji scripts/deploy.sh, tes dari HP & susun log troubleshooting
       │
[Tahap 5: Tim]      ──> Rekam layar individu masing-masing (3–4 menit) untuk interview Milestone M4!
```

---

## 🎯 Pertanyaan Checkpoint Minggu 12 (Persiapan Interview Rekaman Layar M4)

Setiap anggota wajib mempersiapkan jawaban untuk pertanyaan berikut saat merekam video:

1. **Penguji akan menjalankan `curl -I https://domain/.env`. Mengapa hasilnya tidak boleh 200?**  
   *Jawaban:* Jika mengembalikan 200, artinya file `.env` berhasil diunduh publik, yang membocorkan database credentials, app key, dan rahasia sistem kepada penyerang. Hasil aman adalah 404 (karena document root benar di folder `/public`) atau 403 (karena ditolak oleh blokir Nginx).
2. **Apa yang terjadi jika `APP_DEBUG=true` aktif di server produksi?**  
   *Jawaban:* Setiap kali terjadi exception/error, Laravel akan menampilkan layar debug lengkap yang memuat cuplikan source code, path direktori server, cookie session pengguna, query SQL beserta isinya, dan data formulir yang baru saja disubmit.
3. **Mengapa document root Nginx wajib mengarah ke `public/`, bukan ke folder root proyek?**  
   *Jawaban:* Di Laravel, hanya folder `public/` yang berisi file untuk konsumsi publik (`index.php`, build aset Vite). Jika mengarah ke root proyek, file internal seperti `.env`, `composer.json`, dan file storage privat dapat diakses langsung via URL browser.
4. **Apa fungsi Supervisor dan mengapa kita tidak boleh hanya menjalankan `php artisan queue:work` manual di terminal SSH?**  
   *Jawaban:* Jika dijalankan manual di terminal SSH, proses worker akan mati seketika saat sesi SSH ditutup (logout), sehingga antrean notifikasi berhenti diam-diam. Supervisor bertindak sebagai daemon yang memastikan worker berjalan terus di latar belakang dan otomatis me-restart worker jika terjadi crash.
5. **Mengapa perintah `php artisan queue:restart` wajib dijalankan setelah setiap deployment?**  
   *Jawaban:* Worker Laravel memuat kode aplikasi ke dalam memori RAM saat pertama kali dijalankan. Tanpa `queue:restart`, worker lama akan tetap mengeksekusi logika kode versi lama di memori dan tidak menerapkan perubahan kode baru yang baru saja di-pull.
6. **Setelah `php artisan config:cache` dieksekusi di server, apa yang terjadi jika ada kode yang memanggil fungsi `env()` di luar folder `config/`?**  
   *Jawaban:* Fungsi `env()` akan mengembalikan nilai `null` karena file `.env` tidak lagi dibaca oleh Laravel setelah cache konfigurasi terbentuk. Semua pembacaan konfigurasi di aplikasi wajib menggunakan helper `config('...')`.

