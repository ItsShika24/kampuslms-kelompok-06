# Laporan Praktikum Minggu 12 — Deployment & Milestone M4 (Production Ready)
## Nama: Raihandy Wijaya
## NIM: 10241064

---

## 1. Tahap 1: READ — Bedah Server Produksi dari Perspektif Keamanan & Hardening (40 Menit)

Pada tahap awal penyiapan lingkungan produksi, dilakukan analisis mendalam terhadap postur keamanan infrastruktur server VPS Ubuntu 24.04 dan konfigurasi web server Nginx. Sesuai spesifikasi Modul 12 (Milestone M4: *Production Ready*), aplikasi web yang dipublikasikan ke internet menghadapi ancaman pemindaian otomatis (*automated vulnerability scanner*) tanpa henti. Sebagai penanggung jawab *Security Hardening*, fokus utama saya adalah memastikan tidak ada celah informasi yang bocor, file konfigurasi terlindungi, dan seluruh mekanisme otorisasi tetap ditegakkan secara ketat di domain publik.

### 1.1 Penelusuran 6 Poin READ Modul Minggu 12 dari Sudut Pandang Security Engineer

1. **Konfigurasi Web Server dan Penentuan Document Root:**
   * **Baris Penentu Kritis:** Direktif `root /var/www/kampuslms-kelompok-06/public;` pada blok server Nginx ([`scripts/nginx-kampuslms.conf`](../../scripts/nginx-kampuslms.conf) baris 17).
   * **Analisis Celah (*Threat Modeling*):** Di framework Laravel, hanya subdirektori `public/` yang dirancang untuk menerima request web langsung (`index.php` dan aset terkompilasi Vite). Jika seorang sysadmin salah mengarahkan direktif `root` ke direktori induk proyek (`/var/www/kampuslms-kelompok-06`), seluruh struktur kode sumber, file lingkungan `.env`, dependensi `vendor/`, dan log rahasia dapat diunduh langsung lewat peramban internet tanpa melalui interpretasi PHP.

2. **Perbandingan Hasil `php artisan about` (Lokal vs Produksi):**
   * **Lingkungan Lokal (Development):**
     - `Environment`: `local`
     - `Debug Mode`: `ENABLED (true)`
     - `URL`: `http://127.0.0.1:8000` (atau `http://localhost`)
     - `Config / Route / View / Event Cache`: `NOT CACHED`
     - `Session Driver`: `file` / `database`
   * **Lingkungan Produksi Server (Production Live):**
     - `Environment`: `production`
     - `Debug Mode`: **`DISABLED (false)`** (Mandatori mutlak evaluasi M4)
     - `URL`: `https://kampuslms-kelompok-06.proweb.my.id` (Enkripsi TLS 1.3 via Let's Encrypt)
     - `Config / Route / View / Event Cache`: **`CACHED`** (Dioptimasi penuh demi performa dan mencegah I/O disk dinamis)
     - `Session Cookie`: `Secure=true`, `HttpOnly=true`, `SameSite=lax`

3. **Pemeriksaan Status Supervisor & Isolasi File Log Worker:**
   * Menjalankan perintah `sudo supervisorctl status` menunjukkan dua proses antrean berjalan stabil di bawah akun pengguna `www-data`:
     ```text
     kampuslms-worker:kampuslms-worker_00   RUNNING   pid 14201, uptime 0:45:12
     kampuslms-worker:kampuslms-worker_01   RUNNING   pid 14202, uptime 0:45:12
     ```
   * **Audit Keamanan Berkas Log Antrean:** Berkas `storage/logs/worker.log` menyimpan riwayat pekerjaan latar belakang yang memuat identifier notifikasi dan data pengguna. File ini terverifikasi berada di luar document root Nginx, sehingga request eksternal ke `https://domain/storage/logs/worker.log` menghasilkan respon aman **404 Not Found**.

4. **Pemeriksaan Berkas Log `storage/logs/laravel.log` & Strategi Penanganan Error:**
   * Pada kondisi `APP_DEBUG=false`, aplikasi Laravel tidak lagi mencetak pesan exception, cuplikan kode, atau *stack trace* ke layar peramban.
   * Saat terjadi kesalahan sistem (HTTP 500), pengguna publik hanya disajikan halaman antarmuka standar (*generic 500 error page*). Seluruh rincian teknis galat (*stack trace*, parameter request, dan timestamp) dialihkan dan dicatat secara rahasia ke `storage/logs/laravel.log`.
   * Akses publik langsung terhadap berkas log ini diblokir oleh web server Nginx dan mengembalikan respon **404 Not Found**.

5. **Eksekusi Pengujian Audit Akses Berkas Sensitif Eksternal (`curl -I`):**
   * Pengujian dilakukan dari komputer lokal eksternal menggunakan perintah `curl -I`:
     - `curl -I https://kampuslms-kelompok-06.proweb.my.id/.env` ➔ **403 Forbidden** (Blokir lapis ganda oleh Nginx `location ~ /\.(?!well-known)`).
     - `curl -I https://kampuslms-kelompok-06.proweb.my.id/storage/logs/laravel.log` ➔ **404 Not Found** (Terisolasi di atas document root).
     - `curl -I https://kampuslms-kelompok-06.proweb.my.id/composer.json` ➔ **404 Not Found** (Aset internal terlindungi).
   * Ketiga endpoint kritis terbukti bebas dari respon **HTTP 200 OK**, memenuhi syarat kelulusan Milestone M4.

6. **Pengujian Responsivitas & Verifikasi Proteksi Session Cookie via HTTPS:**
   * Saat diakses melalui koneksi HTTPS pada peramban seluler (smartphone), seluruh komunikasi terenkripsi penuh.
   * Header respons menyertakan flag `Set-Cookie: kampuslms_session=...; path=/; secure; httponly; samesite=lax`.
   * Flag `secure` menjamin bahwa peramban tidak akan pernah mengirimkan token sesi pengguna jika pengguna secara tidak sengaja berpindah ke protokol `http://` yang tidak aman.

---

## 2. Tahap 2: BREAK — Analisis & Eksperimen 8 Kerusakan Deployment (50 Menit)

> ⛔ **Protokol Keselamatan Eksperimen Modul 12:**  
> Eksperimen 1 dan 2 secara sengaja membuka rahasia arsitektur sistem. Pemindaian internet untuk mencari halaman debug Laravel berlangsung otomatis setiap detik. Oleh karena itu, pengujian 1 dan 2 dilakukan secara terisolasi di balik pembatasan alamat IP pada blok Nginx (`allow <ip-lokal>; deny all;`), menggunakan database percobaan terpisah, dan **segera dinormalkan kembali**. Kredensial database diganti dan `php artisan key:generate` dieksekusi ulang setelah pengamatan selesai.

Berikut adalah tabel hasil pengamatan 8 percobaan kerusakan deployment dengan fokus analisis dampak keamanan dan potensi eksploitasi:

| # | Percobaan Kerusakan (*Action*) | Prediksi Sebelum Mencoba | Hasil Pengamatan Aktual | Bahaya / Pelajaran Utama & Mitigasi |
|:---:|---|---|---|---|
| **1** | **Set `APP_DEBUG=true`, picu error form di server publik** | Layar browser menampilkan halaman debug Laravel berisi detail exception dan request. | **Kebocoran Rahasia Fatal:** Layar memuat cuplikan baris source code, path fisik server `/var/www/...`, kredensial database di stack trace, cookie session, serta nilai input form yang baru disubmit. | **Diskualifikasi Otomatis (Nilai 0):** Penyerang dapat mencuri session cookie untuk pembajakan akun atau membaca query SQL untuk merancang payload SQL Injection. `APP_DEBUG=false` wajib aktif sejak menit awal! |
| **2** | **Arahkan document root Nginx ke root proyek, hapus blokir file titik** | File `.env` dapat diunduh bebas dari internet melalui peramban. | **Seluruh Rahasia Terunduh Bebas:** Mengakses `https://domain/.env` langsung menampilkan seluruh isi database password, app encryption key, dan konfigurasi SMTP dalam bentuk teks polos (HTTP 200). | Document root WAJIB mengarah ke `/public`. Blokir Nginx `location ~ /\.` bertindak sebagai lapisan pertahanan kedua (*defense in depth*). |
| **3** | **Jalankan `config:cache` dengan fungsi `env()` masih dipanggil di controller** | Pemanggilan `env()` di luar `config/` akan menghasilkan nilai `null`. | Controller yang memanggil `env('APP_NAME')` menerima nilai `null`, mengakibatkan judul navigasi kosong atau error fatal *Null Pointer Exception*. | Setelah konfigurasi di-cache, berkas `.env` sengaja tidak dimuat ke memori RAM demi performa. Seluruh baris kode wajib menggunakan helper `config('...')`. |
| **4** | **Hentikan service Supervisor (`supervisorctl stop all`), buat tugas baru** | Tugas tersimpan di database, tetapi notifikasi tidak pernah sampai ke mahasiswa. | **Kegagalan Senyap (*Silent Failure*):** Web dosen merespons sukses (HTTP 302), namun data pekerjaan menumpuk di tabel `jobs` dan tabel `notifications` kosong tanpa ada notifikasi yang terkirim. | Fitur background processing membutuhkan daemon konstan. Supervisor wajib dikonfigurasi auto-start dan dipantau berkala melalui `supervisorctl status`. |
| **5** | **Deploy kode baru di server tanpa menjalankan `queue:restart`** | Worker antrean tetap mengeksekusi logika kode lama yang tersimpan di RAM. | Worker memproses event notifikasi dengan template pesan lama yang sudah usang. Perubahan logika bisnis yang baru di-pull dari Git diabaikan oleh worker. | Worker Laravel bersifat *long-lived process*. Perintah `php artisan queue:restart` wajib dieksekusi pada setiap alur deploy untuk melepaskan cache memori worker. |
| **6** | **Ubah hak akses folder `storage` menjadi `chmod 555`** | Aplikasi mengalami HTTP 500 karena tidak dapat menulis file log dan view cache. | Browser menerima error HTTP 500 kosong, dan bahkan file `laravel.log` tidak dapat mencatat error tersebut karena web server kehilangan hak tulis (*Permission Denied*). | Akun `www-data` wajib memiliki kepemilikan dan hak tulis penuh: `chown -R www-data:www-data storage bootstrap/cache` dan `chmod -R 775`. |
| **7** | **Set `upload_max_filesize=1M` di `php.ini`, unggah berkas tugas 3 MB** | Unggahan ditolak oleh server PHP sebelum validasi Form Request Laravel berjalan. | Browser menerima error *The uploaded file exceeds the upload_max_filesize directive in php.ini* atau koneksi diputus sepihak oleh Nginx/PHP-FPM. | Batas ukuran berkas di `php.ini` (`upload_max_filesize` & `post_max_size`) dan Nginx (`client_max_body_size`) wajib lebih besar dari batas validasi aplikasi (20 MB). |
| **8** | **Buka website via `http://` setelah `SESSION_SECURE_COOKIE=true` aktif** | Peramban menolak mengirimkan cookie sesi karena koneksi tidak aman (tanpa SSL). | Pengguna mengalami *infinite login loop*; setiap kali form login disubmit, pengguna kembali diarahkan ke form login karena token session ditolak oleh peramban. | Flag `Secure` mewajibkan komunikasi terenkripsi. Nginx wajib mengalihkan seluruh lalu lintas port 80 ke HTTPS port 443 dengan kode 301. |

---

## 3. Tahap 3: FIX — Audit dan Perbaikan Branch `w12` Repo `kampuslms-broken` (30 Menit)

Pada branch `w12` repositori `kampuslms-broken`, dilakukan audit dan perbaikan terhadap **7 masalah konfigurasi deployment**, dengan fokus tanggung jawab saya pada pencegahan kebocoran data dan pengerasan keamanan:

1. **Kredensial Produksi Nyata Tersimpan di `.env.example`:**
   * *Celah Keamanan:* Kata sandi database dan kunci sistem nyata bocor ke publik melalui riwayat commit Git.
   * *Perbaikan:* Menghapus kredensial nyata dan menggantinya dengan nilai *dummy placeholder* (`DB_PASSWORD=secret_db_password_here`).
2. **Skrip `scripts/deploy.sh` Berjalan Tanpa Flag `set -e`:**
   * *Celah Keamanan:* Jika satu perintah kritis gagal (misal migrasi skema gagal), skrip tetap melanjutkan ke tahap berikutnya sehingga server berada dalam kondisi inkonsisten.
   * *Perbaikan:* Menambahkan `set -e` pada baris awal skrip agar eksekusi berhenti seketika saat ada perintah yang menghasilkan exit code non-zero.
3. **Ketiadaan Perintah `php artisan queue:restart` pada Skrip Deployment:**
   * *Celah Keamanan:* Worker antrean terus mengeksekusi kode usang dari memori RAM setelah kode baru ditarik dari Git.
   * *Perbaikan:* Menyisipkan perintah `php artisan queue:restart` setelah migrasi dan cache dibangun ulang.
4. **Konfigurasi Virtual Host Nginx Mengarah ke Root Repositori:**
   * *Celah Keamanan:* Membocorkan berkas internal `.env`, direktori log, konfigurasi dependensi, dan kode sumber ke seluruh pengguna internet.
   * *Perbaikan:* Mengubah direktif `root /var/www/kampuslms;` menjadi `root /var/www/kampuslms/public;`.
5. **Nilai Default `APP_DEBUG=true` di Template Konfigurasi Produksi:**
   * *Celah Keamanan:* Berpotensi menyalakan layar debug otomatis di server publik saat konfigurasi dibuat dari template.
   * *Perbaikan:* Mengubah default konfigurasi template menjadi `APP_DEBUG=false` dan `APP_ENV=production`.
6. **Perintah `db:seed` Dijalankan Secara Otomatis di Setiap Siklus Deployment:**
   * *Celah Integritas:* Berpotensi menimpa atau merusak data transaksi nyata, submission tugas, dan nilai mahasiswa di server produksi.
   * *Perbaikan:* Menghapus `db:seed` dari alur rutin skrip `deploy.sh`. Inisialisasi seeder hanya boleh dijalankan secara manual satu kali saat setup awal.
7. **File `.env` Tidak Terdaftar di Berkas `.gitignore`:**
   * *Celah Keamanan:* Pengembang berisiko secara tidak sengaja memasukkan berkas konfigurasi lokal atau produksi yang memuat kunci rahasia ke repositori GitHub publik.
   * *Perbaikan:* Menambahkan baris `.env` dan `.env.backup` pada file `.gitignore`.

---

## 4. Tahap 4: BUILD — Kontribusi Nyata Raihandy Wijaya (Milestone M4)

Sebagai penanggung jawab *Security Hardening & Penetration Testing* pada Kelompok 06, berikut adalah rincian deliverable teknis yang telah saya selesaikan:

### 4.1 Penyusunan Skrip Audit Otomasi [`scripts/audit-security.sh`](../../scripts/audit-security.sh)
Untuk mempermudah tim dan penguji mengevaluasi kepatuhan keamanan server secara komprehensif, saya merancang skrip audit mandiri berbasis Bash. Skrip ini secara otomatis memvalidasi 12 titik pertahanan sistem:

```bash
# Menjalankan skrip audit keamanan terhadap domain produksi live
bash scripts/audit-security.sh https://kampuslms-kelompok-06.proweb.my.id
```

**Keluaran Terminal Skrip Audit Keamanan:**
```text
================================================================
     KAMPUSLMS — PRODUCTION SECURITY AUDIT SUITE (MINGGU 12)    
     Auditor : Raihandy Wijaya (NIM: 10241064)                  
     Target  : https://kampuslms-kelompok-06.proweb.my.id       
================================================================

>>> 1. AUDIT AKSES BERKAS SENSITIF & STRUKTUR INTERNAL (WAJIB 403/404) <<<
  [PASS] [HTTP 403] Konfigurasi Environment Kritis (.env) -> /.env
  [PASS] [HTTP 403] File Template Environment (.env.example) -> /.env.example
  [PASS] [HTTP 403] Konfigurasi Repositori Git (.git/config) -> /.git/config
  [PASS] [HTTP 403] Pointer Branch Git (.git/HEAD) -> /.git/HEAD
  [PASS] [HTTP 404] Log Internal Aplikasi Laravel (laravel.log) -> /storage/logs/laravel.log
  [PASS] [HTTP 404] Log Background Worker Supervisor (worker.log) -> /storage/logs/worker.log
  [PASS] [HTTP 404] Manifest Dependensi Composer (composer.json) -> /composer.json
  [PASS] [HTTP 404] Lockfile Dependensi Composer (composer.lock) -> /composer.lock
  [PASS] [HTTP 404] Manifest Frontend Node.js (package.json) -> /package.json
  [PASS] [HTTP 404] Lockfile Dependensi Node.js (package-lock.json) -> /package-lock.json
  [PASS] [HTTP 404] Konfigurasi Unit Testing (phpunit.xml) -> /phpunit.xml
  [PASS] [HTTP 403] Database Berkas Langsung (SQLite) -> /database/database.sqlite

>>> 2. AUDIT ENKRIPSI PROTOKOL & REDIRECT OTOMATIS HTTPS <<<
  [PASS] [HTTP 301] Redirect HTTP -> HTTPS aktif untuk http://kampuslms-kelompok-06.proweb.my.id

>>> 3. AUDIT HEADER KEAMANAN PRODUKSI (NGINX HARDENING) <<<
  [PASS] Header Keamanan Ditemukan: X-Frame-Options: SAMEORIGIN
  [PASS] Header Keamanan Ditemukan: X-Content-Type-Options: nosniff
  [PASS] Header Keamanan Ditemukan: X-XSS-Protection: 1

>>> 4. AUDIT PROTEKSI MODE PRODUKSI (VERIFIKASI APP_DEBUG=false) <<<
  [PASS] Bebas Jejak Debug: Halaman 404 bersih tanpa eksposur stack trace internal (APP_DEBUG=false terkonfirmasi)

>>> 5. AUDIT KEAMANAN UNDUHAN BERKAS PRIVAT (ANTI-BYPASS AUTH) <<<
  [PASS] [HTTP 302] Berkas submission privat terlindungi dari akses anonim (Wajib Login)

================================================================
  HASIL AUDIT: SELURUH TITIK KRITIS LOLOS AUDIT KEAMANAN! (100%)
  Total Titik Diuji: 18 | Lolos: 18 | Gagal: 0        
  Status: MEMENUHI PERSYARATAN MILESTONE M4 (PRODUCTION READY)  
================================================================
```

### 4.2 Pemutakhiran Skrip Otorisasi Live [`scripts/test-authz.sh`](../../scripts/test-authz.sh)
Agar skrip pengujian otorisasi dari Minggu 7 dapat dieksekusi secara remote terhadap domain produksi live dengan HTTPS dan mendukung kredensial akun demo produksi, saya melakukan pembaruan fleksibilitas:
* Mendukung variabel lingkungan `BASE_URL` fleksibel (`BASE_URL="${BASE_URL:-${1:-http://127.0.0.1:8000}}"`) sehingga dapat langsung dipanggil dengan sintaks `BASE_URL="https://kampuslms-kelompok-06.proweb.my.id" bash scripts/test-authz.sh`.
* Menambahkan variabel password dinamis (`ADMIN_PASSWORD`, `DOSEN_PASSWORD`, `MHS_PASSWORD`) yang otomatis kompatibel dengan akun demo produksi tanpa mengubah berkas skrip.
* Menambahkan pengujian khusus keamanan unduhan berkas submission privat tanpa sesi autentikasi (`GET /submissions/{id}/download`), memastikan respon dialihkan ke login (HTTP 302/403).

### 4.3 Verifikasi Otorisasi Anti-IDOR pada Server Produksi Live
Skrip `test-authz.sh` dieksekusi terhadap domain publik live dan menghasilkan **25 passed dari 25 pengujian (100% Lolos)**:
1. **Proteksi Tamu (Guest Protection):** Seluruh rute privat (`/dashboard`, `/mata-kuliah`, `/admin/users`, `/submissions`, dan unduhan berkas) dialihkan dengan HTTP 302 ke `/login`, serta API menghasilkan HTTP 401 Unauthorized.
2. **Mitigasi IDOR Mahasiswa:** Mahasiswa dilarang keras membuka atau mengunduh submission milik mahasiswa lain (HTTP 403 Forbidden). Mahasiswa hanya memiliki akses ke submission miliknya sendiri (HTTP 200 OK). Mahasiswa dilarang membuat mata kuliah atau memberi nilai.
3. **Mitigasi Cross-Course Dosen:** Dosen A dilarang mengedit atau memperbarui mata kuliah milik Dosen B (HTTP 403 Forbidden). Dosen dilarang menghapus mata kuliah (hanya Administrator yang berhak).
4. **Proteksi Session Fixation & Enumeration:** Session ID diperbarui secara otomatis setelah login berhasil, dan pesan kesalahan login bersifat generik (*"Email atau kata sandi yang Anda masukkan tidak sesuai"*), mencegah penyerang menebak keberadaan email pengguna.

### 4.4 Pembaruan Dokumentasi Deployment [`docs/deployment.md`](../deployment.md)
Saya menyusun dan memperbarui **Bagian 5 (Bukti Audit Keamanan Eksternal & Uji Otorisasi Live)** pada panduan resmi deployment dengan menyajikan:
* Tabel matriks audit 10 berkas dan struktur internal sistem.
* Cuplikan header respon `curl -I` nyata untuk `.env`, log aplikasi, log worker, `.git/config`, dan `composer.json`.
* Bukti konfigurasi header keamanan Nginx (`X-Frame-Options`, `X-Content-Type-Options`, `X-XSS-Protection`).
* Log lengkap eksekusi skrip otorisasi anti-IDOR live dan dokumentasi penggunaan `scripts/audit-security.sh`.

---

## 5. Pertanyaan Checkpoint Minggu 12 (Persiapan Rekaman Layar M4 & Interview)

Berikut adalah ringkasan jawaban komprehensif untuk materi rekaman video interview individu Milestone M4 saya:

### 1. Penguji akan menjalankan `curl -I https://domain/.env`. Mengapa hasilnya tidak boleh 200?
**Jawaban:**  
Jika `curl -I https://domain/.env` mengembalikan kode **HTTP 200 OK**, berarti berkas `.env` berhasil diunduh secara bebas oleh publik. Berkas tersebut memuat informasi paling rahasia dari seluruh sistem: kunci enkripsi aplikasi (`APP_KEY`), nama pengguna dan kata sandi database MySQL (`DB_PASSWORD`), kredensial server email, serta kunci API pihak ketiga. Penyerang yang memperoleh berkas ini dapat langsung membobol database dan mendekripsi seluruh session pengguna. Hasil yang sah dan aman wajib **404 Not Found** (karena document root web server diarahkan dengan benar ke subfolder `/public`) atau **403 Forbidden** (karena ditolak oleh aturan blokir berkas tersembunyi pada Nginx).

### 2. Apa yang terjadi jika `APP_DEBUG=true` aktif di server produksi?
**Jawaban:**  
Jika `APP_DEBUG=true` aktif di server publik, setiap kali terjadi kesalahan input formulir, query gagal, atau *unhandled exception*, framework Laravel (melalui Ignition/Flare) akan menampilkan layar debug interaktif secara visual. Layar ini membocorkan:
1. Cuplikan baris source code aplikasi di sekitar baris yang memicu error.
2. Struktur path direktori internal server (misal `/var/www/kampuslms-kelompok-06/app/...`).
3. Seluruh variabel lingkungan dan parameter request pengguna (termasuk password yang baru diketik jika tidak disaring).
4. Kredensial database pada *database stack trace*.
5. Token cookie sesi pengguna (*session cookie*) yang dapat dipakai penyerang untuk melakukan *session hijacking*.
Pada server produksi, `APP_DEBUG` wajib bernilai **`false`**. Informasi error tidak boleh ditampilkan ke publik, melainkan dialihkan secara aman ke berkas internal `storage/logs/laravel.log`.

### 3. Mengapa document root Nginx wajib mengarah ke `public/`, bukan ke folder root proyek?
**Jawaban:**  
Pada arsitektur framework Laravel, hanya subfolder `public/` yang berisi berkas *front controller* (`index.php`) dan aset statis terkompilasi (CSS, JS, gambar). Seluruh direktori lainnya (`app/`, `config/`, `database/`, `routes/`, `storage/`, `.env`, `composer.json`) berisi logika bisnis dan konfigurasi rahasia. Jika document root diarahkan ke root proyek (`/var/www/kampuslms`), maka web server Nginx akan memperlakukan seluruh folder proyek sebagai berkas statis yang dapat diakses langsung melalui URL peramban.

### 4. Apa fungsi Supervisor dan mengapa kita tidak boleh hanya menjalankan `php artisan queue:work` manual di terminal SSH?
**Jawaban:**  
Supervisor adalah sistem *process monitor/daemon* pada Linux yang bertugas menjaga agar proses worker antrean (`php artisan queue:work`) tetap hidup secara terus-menerus di latar belakang (*background*). Jika perintah `queue:work` dijalankan manual lewat terminal SSH:
1. Proses worker akan langsung mati saat koneksi SSH terputus atau terminal ditutup (*logout*).
2. Terjadi kegagalan senyap (*silent failure*): aplikasi web dosen tetap merespons sukses saat membuat tugas atau penilaian, namun notifikasi database ke puluhan mahasiswa tertahan di tabel `jobs` dan tidak pernah dikirimkan.
3. Jika proses mengalami *crash* karena kehabisan memori (*memory leak*), tidak ada mekanisme yang menyalakannya kembali.
Supervisor mengatasi hal ini dengan fitur `autostart=true` (otomatis menyala saat boot) dan `autorestart=true` (otomatis menyalakan ulang proses jika terjadi crash).

### 5. Mengapa perintah `php artisan queue:restart` wajib dijalankan setelah setiap deployment?
**Jawaban:**  
Worker antrean Laravel dirancang sebagai *long-lived process*. Saat pertama kali dijalankan, worker membaca dan memuat seluruh baris kode aplikasi ke dalam memori RAM server, lalu terus berjalan tanpa membaca ulang disk. Ketika pengembang melakukan pembaruan kode (`git pull`) di server, worker yang sedang berjalan **TIDAK** memuat perubahan file baru tersebut dari disk. Worker akan terus mengeksekusi logika kode versi lama yang tertahan di memori RAM. Perintah `php artisan queue:restart` memberikan instruksi sinyal kepada worker yang sedang berjalan untuk menyelesaikan tugas aktifnya secara anggun (*graceful exit*), lalu keluar dari proses. Supervisor kemudian secara otomatis mendeteksi proses berhenti dan menyalakan proses worker baru yang memuat kode versi terbaru dari disk ke dalam RAM.

### 6. Setelah `php artisan config:cache` dieksekusi di server, apa yang terjadi jika ada kode yang memanggil fungsi `env()` di luar folder `config/`?
**Jawaban:**  
Fungsi `env()` akan selalu mengembalikan nilai **`null`**! Setelah perintah `config:cache` dieksekusi, Laravel menggabungkan seluruh konfigurasi dari folder `config/*.php` ke dalam satu berkas array PHP terkompilasi di `bootstrap/cache/config.php` dan sengaja tidak lagi menginisialisasi pustaka *dotenv* (`.env`). Jika ada controller atau seeder yang memanggil `env('NAMA_KEY')`, sistem tidak akan membaca berkas `.env` dan langsung mengembalikan `null`, yang menyebabkan aplikasi mengalami crash HTTP 500 di server produksi meskipun di komputer lokal berjalan normal. Seluruh pembacaan konfigurasi di aplikasi wajib menggunakan helper `config('app.key')`.

### 7. Buka `docs/deployment.md`. Ceritakan satu masalah yang Anda temui dan bagaimana Anda menemukan penyebabnya.
**Jawaban:**  
Masalah kritis yang ditemukan selama proses audit keamanan adalah **akses berkas submission tugas mahasiswa oleh pengguna tanpa login (*unauthenticated guest*)**. Pada evaluasi awal pengujian unduhan berkas di `GET /submissions/{id}/download`, ditemukan potensi bypass jika controller tidak diproteksi oleh middleware `auth` dan policy otorisasi yang ketat.  
*Investigasi:* Saat melakukan pengujian curl anonim (`curl -I https://domain/submissions/1/download`), status respons sempat menghasilkan unduhan file langsung jika file diletakkan di subfolder `public/storage/`.  
*Solusi Hardening:* Sesuai materi Minggu 9 dan 12, seluruh berkas submission disimpan di disk privat lokal non-publik (`storage/app/private/submissions`), dan rute unduhan diwajibkan melewati controller ber-middleware `auth` yang memeriksa `$this->authorize('view', $submission)`. Dengan mitigasi ini, akses anonim dipastikan selalu dialihkan ke halaman login (HTTP 302), dan mahasiswa yang tidak terdaftar ditolak dengan HTTP 403 Forbidden.

### 8. Tunjukkan satu bagian yang Anda tulis dengan bantuan AI. Apa yang Anda ubah, dan kenapa?
**Jawaban:**  
Bagian yang saya susun dengan bantuan AI adalah skrip audit otomatisasi [`scripts/audit-security.sh`](../../scripts/audit-security.sh) dan skrip pengujian otorisasi [`scripts/test-authz.sh`](../../scripts/test-authz.sh).  
*Perubahan yang Saya Lakukan:*  
1. AI awalnya membuat skrip otorisasi dengan alamat *hardcoded* `http://127.0.0.1:8000` dan kata sandi statis `password`. Saya mengubahnya menjadi variabel lingkungan fleksibel `BASE_URL="${BASE_URL:-${1:-http://127.0.0.1:8000}}"` dan mendukung variabel password akun demo produksi (`ADMIN_PASSWORD`, dll.), sehingga skrip yang sama dapat langsung dieksekusi terhadap domain publik produksi HTTPS.  
2. AI awalnya hanya memeriksa file `.env` dan `storage/logs/laravel.log`. Saya memperluas skrip audit keamanan dengan menambahkan 10 titik pemeriksaan tambahan, termasuk `.git/config`, `storage/logs/worker.log`, `composer.json`, `phpunit.xml`, header keamanan Nginx (`X-Frame-Options`, `X-Content-Type-Options`), verifikasi tanda *signature* debug Laravel untuk membuktikan `APP_DEBUG=false`, serta verifikasi anti-bypass pada rute unduhan berkas submission privat. Perubahan ini memastikan seluruh kriteria penilaian Milestone M4 terpenuhi 100%.

