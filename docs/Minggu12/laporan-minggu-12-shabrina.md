# Laporan Praktikum Minggu 12 — Deployment & Milestone M4 (Production Ready)
## Nama: Shabrina Putri Az-Zahra
## NIM: 10241066
## Kelompok: Kelompok 06 (EduKampus)
**Peran Utama:** Asynchronous Systems & Background Worker  
**Repositori:** KampusLMS (Laravel 12)  
**Dokumen Pendukung Utama:** [`docs/deployment.md`](../deployment.md) & [`scripts/kampuslms-worker.conf`](../../scripts/kampuslms-worker.conf)  

---

## 1. Tahap 1: READ — Bedah Server & Siklus Hidup Worker Supervisor (40 Menit)

Pada tahap awal penyiapan lingkungan produksi, dilakukan analisis mendalam terhadap infrastruktur pemrosesan asinkron (*asynchronous background processing*) pada server VPS Ubuntu 24.04. Sesuai spesifikasi Modul 10 dan Modul 12, pengiriman notifikasi publikasi tugas dan penilaian tidak boleh membebani siklus request HTTP pengguna web, melainkan dialihkan ke antrean basis data (*database queue*) yang dieksekusi secara konstan oleh Supervisor.

### 1.1 Penelusuran 6 Poin READ Modul Minggu 12 dari Sudut Pandang Background Worker

1. **Konfigurasi Web Server dan Penentuan Document Root:**
   * Konfigurasi Nginx diatur dengan direktif `root /var/www/kampuslms-kelompok-06/public;`.
   * **Analisis Keamanan Worker:** File log internal antrean yang berlokasi di `/var/www/kampuslms-kelompok-06/storage/logs/worker.log` dan file konfigurasi Supervisor sepenuhnya berada di luar folder `public/`, sehingga riwayat pengiriman notifikasi dan payload data tidak dapat diintip oleh publik melalui URL peramban.

2. **Perbandingan Hasil `php artisan about` (Lokal vs Produksi):**
   * **Lingkungan Lokal:**
     - `Environment`: `local`
     - `Debug Mode`: `ENABLED (true)`
     - `Queue Connection`: `sync` atau `database` (dijalankan ad-hoc via terminal)
     - `Config / Route / View / Event Cache`: `NOT CACHED`
   * **Lingkungan Produksi Server:**
     - `Environment`: `production`
     - `Debug Mode`: `DISABLED (false)`
     - `Queue Connection`: `database` (dikelola secara tangguh oleh Supervisor daemon)
     - `Config / Route / View / Event Cache`: `CACHED` (dioptimasi penuh untuk kecepatan baca)

3. **Pemeriksaan Status Supervisor di Server Produksi:**
   * Menjalankan perintah `sudo supervisorctl status` menunjukkan dua proses worker berjalan secara stabil dan independen:
     ```text
     kampuslms-worker:kampuslms-worker_00   RUNNING   pid 14201, uptime 0:45:12
     kampuslms-worker:kampuslms-worker_01   RUNNING   pid 14202, uptime 0:45:12
     ```
   * Kedua proses berjalan di bawah pengguna `www-data` dan otomatis menyala sejak sistem boot (`autostart=true`).

4. **Pemeriksaan Berkas Log `storage/logs/worker.log`:**
   * Berkas log mencatat setiap eksekusi listener secara rapi dengan waktu proses dalam milidetik:
     ```text
     [2026-11-06 14:15:22] App\Listeners\SendAssignmentNotification ................ RUNNING
     [2026-11-06 14:15:22] App\Listeners\SendAssignmentNotification ............... 18.42ms DONE
     ```
   * Tidak ditemukan entri job yang gagal atau berulang-ulang gagal (*zero failed jobs*).

5. **Eksekusi Pengujian Audit Akses Berkas Sensitif (`curl -I`):**
   * `curl -I https://kampuslms-kelompok-06.proweb.my.id/.env` ➔ **403 Forbidden** (blokir Nginx).
   * `curl -I https://kampuslms-kelompok-06.proweb.my.id/storage/logs/worker.log` ➔ **404 Not Found** (terisolasi di luar document root).
   * `curl -I https://kampuslms-kelompok-06.proweb.my.id/composer.json` ➔ **404 Not Found**.

6. **Pengujian Responsivitas End-to-End & Uji Beban Notifikasi:**
   * Saat akun Dosen menerbitkan satu tugas baru pada mata kuliah dengan 30 mahasiswa, halaman web Dosen langsung merespons pengalihan halaman (*redirect*) dalam waktu **120 ms**.
   * Dosen tidak mengalami *freeze* atau *loading* lama karena 30 proses penulisan ke tabel `notifications` dikerjakan oleh 2 worker Supervisor di latar belakang secara paralel.

---

## 2. Tahap 2: BREAK — Tabel Eksperimen 8 Kerusakan Deployment (50 Menit)

> ⛔ **Protokol Keselamatan Eksperimen Modul 12:**  
> Eksperimen 1 dan 2 membuka informasi sensitif dan rahasia sistem. Sesuai instruksi modul, pengujian dilakukan di balik pembatasan alamat IP pada Nginx (`allow <ip-lokal>; deny all;`), menggunakan database percobaan, dan **segera dinormalkan kembali**. Kredensial database diperbarui dan `php artisan key:generate` dieksekusi ulang setelah pengujian selesai. Jam mulai dan selesai dicatat di dokumentasi.

Berikut adalah tabel hasil pengamatan 8 percobaan kerusakan deployment dengan fokus khusus pada keandalan sistem asinkron:

| # | Percobaan Kerusakan (*Action*) | Prediksi Sebelum Mencoba | Hasil Pengamatan Aktual | Bahaya / Pelajaran Utama & Mitigasi |
|:---:|---|---|---|---|
| **1** | Set `APP_DEBUG=true`, picu error form di server publik | Halaman error Laravel akan menampilkan detail exception dan variabel request. | **Kebocoran Fatal:** Layar peramban menampilkan cuplikan source code, kredensial database di stack trace, cookie session pengguna, query SQL, dan isi form submission. | **Diskualifikasi Otomatis (Nilai 0):** `APP_DEBUG=false` wajib aktif sejak menit pertama deployment di server publik. |
| **2** | Arahkan document root Nginx ke root proyek (`/var/www/...`), hapus blokir file titik | Seluruh file di repositori dapat diunduh langsung lewat peramban. | **Seluruh Rahasia Terunduh:** Mengakses `https://domain/.env` langsung menampilkan teks polos berisi `DB_PASSWORD` dan `APP_KEY` (HTTP 200). | Document root WAJIB mengarah ke `/public`. Blokir Nginx `location ~ /\.` menjadi lapis pertahanan kedua. |
| **3** | Jalankan `config:cache` sementara masih ada pemanggilan `env()` di controller | Fungsi `env()` akan mengembalikan nilai `null`. | Controller yang memanggil `env('APP_NAME')` menghasilkan string kosong / `null` sehingga tampilan layout dan notifikasi cacat. | Setelah cache aktif, berkas `.env` tidak lagi dibaca Laravel. Wajib selalu menggunakan helper `config('...')`. |
| **4** | **Hentikan service Supervisor (`sudo supervisorctl stop all`), lalu buat tugas baru** | Tugas tersimpan di database, tetapi notifikasi tidak terkirim. | **Kegagalan Senyap (*Silent Failure*):** Web dosen merespons sukses (HTTP 302) tanpa error apa pun. Namun, baris pekerjaan tertahan di tabel `jobs` dan tabel `notifications` kosong. Mahasiswa tidak menerima notifikasi lonceng. | **Risiko Produksi Tertinggi:** Aplikasi terlihat normal padahal fitur background mati total. Mitigasi: Supervisor wajib dikonfigurasi sebagai daemon auto-start dan dipantau via `supervisorctl status`. |
| **5** | **Deploy kode baru di server tanpa menjalankan `php artisan queue:restart`** | Worker tetap memproses antrean dengan logika kode lama. | Worker mengeksekusi job menggunakan kode lama yang tersimpan di memori RAM. Perubahan teks template notifikasi yang baru di-pull dari Git tidak muncul di notifikasi mahasiswa. | Worker Laravel bersifat *long-lived process*. `php artisan queue:restart` wajib dieksekusi pada setiap alur deploy untuk memaksa worker memuat ulang kode dari disk. |
| **6** | Ubah izin direktori `storage` menjadi `chmod 555` | Aplikasi mengalami error 500 karena tidak dapat menulis file log dan cache. | Muncul error HTTP 500 kosong di browser, dan worker Supervisor langsung crash berulang kali (*FATAL*) karena tidak dapat menulis ke `storage/logs/worker.log`. | Pengguna `www-data` wajib memiliki hak kepemilikan dan tulis penuh (`chmod -R 775` dan `chown -R www-data:www-data storage bootstrap/cache`). |
| **7** | Set `upload_max_filesize=1M` di `php.ini`, unggah tugas PDF 3 MB | Unggahan tugas ditolak oleh PHP sebelum validasi Laravel berjalan. | Browser menerima error *The uploaded file exceeds the upload_max_filesize directive in php.ini* atau koneksi diputus sepihak. | Batas ukuran berkas di `php.ini` (`upload_max_filesize` & `post_max_size`) dan Nginx (`client_max_body_size`) wajib sinkron dengan validasi aplikasi (20 MB). |
| **8** | Akses website via HTTP biasa setelah `SESSION_SECURE_COOKIE=true` aktif | Cookie session tidak dikirimkan oleh browser karena koneksi tidak aman. | Pengguna tidak dapat login. Setiap kali form login disubmit, pengguna kembali ke halaman login karena session cookie ditolak oleh browser. | Flag `Secure` mewajibkan komunikasi terenkripsi. Nginx wajib me-redirect seluruh port 80 ke HTTPS port 443 dengan kode 301. |

---

## 3. Tahap 3: FIX — Perbaikan Repo Cacat `kampuslms-broken` Branch `w12` (30 Menit)

Pada branch `w12` repositori `kampuslms-broken`, ditemukan dan diperbaiki **7 masalah konfigurasi deployment**, dengan fokus tanggung jawab saya pada keandalan worker dan otomasi antrean:

1. **Kredensial Produksi Nyata Tersimpan di `.env.example`:**
   * *Bahaya:* Kata sandi database dan kunci sistem bocor ke publik melalui kontrol versi Git.
   * *Perbaikan:* Menghapus kredensial nyata dan menggantinya dengan nilai *dummy placeholder*.
2. **Skrip `scripts/deploy.sh` Berjalan Tanpa Flag `set -e`:**
   * *Bahaya:* Jika satu perintah gagal (misal migrasi gagal), skrip tetap melanjutkan ke tahap berikutnya sehingga server berada dalam kondisi inkonsisten.
   * *Perbaikan:* Menambahkan `set -e` pada baris awal skrip untuk menghentikan eksekusi seketika jika ada perintah yang menghasilkan exit code non-zero.
3. **Ketiadaan Perintah `php artisan queue:restart` pada Skrip Deployment (Fokus Shabrina):**
   * *Bahaya:* Worker Supervisor terus menjalankan baris kode lama yang telah usang dari memori RAM setelah kode baru ditarik dari Git.
   * *Perbaikan:* Menyisipkan perintah `php artisan queue:restart || true` pada baris 45 skrip [`scripts/deploy.sh`](../../scripts/deploy.sh) agar worker melepaskan memori lama dan memuat ulang kode termutakhir.
4. **Konfigurasi Virtual Host Nginx Mengarah ke Root Repositori:**
   * *Bahaya:* Membocorkan berkas internal `.env`, direktori log, dan dependensi sistem ke publik.
   * *Perbaikan:* Mengoreksi direktif `root` agar mengarah tepat ke subfolder `/public`.
5. **Ketiadaan Berkas Konfigurasi Supervisor Daemon (Fokus Shabrina):**
   * *Bahaya:* Pengembang terpaksa menjalankan `php artisan queue:work` secara manual di sesi terminal SSH. Begitu koneksi SSH ditutup, proses worker mati dan antrean berhenti tanpa jejak.
   * *Perbaikan:* Menyusun berkas template konfigurasi daemon Supervisor di [`scripts/kampuslms-worker.conf`](../../scripts/kampuslms-worker.conf) dengan manajemen auto-restart dan alokasi 2 worker process.
6. **Perintah `db:seed` Dijalankan Secara Otomatis di Setiap Deployment:**
   * *Bahaya:* Menimpa data riil transaksi, submission tugas, dan nilai mahasiswa di server produksi.
   * *Perbaikan:* Menghapus pemanggilan seeder dari alur deploy rutin dan membatasinya hanya untuk inisialisasi awal.
7. **Berkas `.env` Tidak Dikecualikan di `.gitignore`:**
   * *Bahaya:* Berkas konfigurasi lokal/server rentan ter-commit ke repositori GitHub publik.
   * *Perbaikan:* Memastikan berkas `.env` dan variasinya terdaftar rapi pada file `.gitignore`.

---

## 4. Tahap 4: BUILD — Kontribusi Nyata Shabrina Putri Az-Zahra (Milestone M4)

Sebagai penanggung jawab sistem pemrosesan asinkron (*Asynchronous Systems & Background Worker*) pada Kelompok 06, berikut adalah deliverable teknis yang telah saya bangun dan verifikasi untuk Milestone M4:

### 4.1 Pembuatan Template Konfigurasi Supervisor Daemon
Telah disusun berkas template konfigurasi resmi di [`scripts/kampuslms-worker.conf`](../../scripts/kampuslms-worker.conf) dengan konfigurasi teruji:

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

### 4.2 Verifikasi Status Worker Produksi
Worker berhasil dimuat dan dijalankan via Supervisor dengan bukti status terminal:

```bash
$ sudo supervisorctl status
kampuslms-worker:kampuslms-worker_00   RUNNING   pid 14201, uptime 0:45:12
kampuslms-worker:kampuslms-worker_01   RUNNING   pid 14202, uptime 0:45:12
```

### 4.3 Penyusunan Dokumentasi Mendalam di `docs/deployment.md`
Telah melengkapi Bagian 4 dokumen [`docs/deployment.md`](../deployment.md) yang mencakup:
* Penjelasan teknis 7 direktif konfigurasi Supervisor (`command`, `numprocs=2`, `user=www-data`, `sleep`, `tries`, `max-time`, `stopwaitsecs`).
* Prosedur lengkap manajemen worker (`reread`, `update`, `start`, `restart`, `status`, `tail`).
* Landasan arsitektur mengapa `php artisan queue:restart` wajib dieksekusi pasca deployment (RAM vs Disk).
* Panduan investigasi dan mitigasi kegagalan senyap (*silent failure*) antrean di server produksi tanpa menyalakan `APP_DEBUG`.

### 4.4 Verifikasi Alur Notifikasi Asinkron di Lingkungan Server
Memastikan bahwa kedua listener antrean yang telah saya bangun pada Milestone M3 berfungsi sempurna di server produksi:
1. **`SendAssignmentNotification` ([`app/Listeners/SendAssignmentNotification.php`](../../app/Listeners/SendAssignmentNotification.php)):**
   * Mengirim notifikasi penugasan baru kepada seluruh mahasiswa terdaftar secara asinkron tanpa query N+1 (`$course->students()->get()`).
   * Dilengkapi mekanisme perlindungan kegagalan: `$tries = 3`, `$backoff = 10`, serta pencatatan log error otomatis via method `failed(\Throwable $exception)`.
2. **`SendGradeNotification` ([`app/Listeners/SendGradeNotification.php`](../../app/Listeners/SendGradeNotification.php)):**
   * Mengirim notifikasi penerimaan nilai secara privat kepada mahasiswa pemilik tugas segera setelah dosen mengisi formulir penilaian.
3. **`NewAssignmentNotification` ([`app/Notifications/NewAssignmentNotification.php`](../../app/Notifications/NewAssignmentNotification.php)):**
   * Mengimplementasikan `ShouldQueue` dengan payload database yang bersih dari data sensitif sesuai standar keamanan Modul 10 Poin 8.

---

## 5. Jawaban Khusus Pembagian Jobdesk Shabrina Putri Az-Zahra

Berikut adalah jawaban komprehensif atas empat poin teknis yang ditugaskan kepada saya pada panduan pembagian tugas:

### 1. Susunan Konfigurasi Lengkap `/etc/supervisor/conf.d/kampuslms-worker.conf`
Berkas konfigurasi menggunakan 2 worker processes (`numprocs=2`) di bawah pengguna `www-data`:

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

### 2. Perintah Terminal Manajemen Worker via `supervisorctl`
* **Reload konfigurasi baru:**
  ```bash
  sudo supervisorctl reread
  ```
* **Terapkan pembaruan konfigurasi:**
  ```bash
  sudo supervisorctl update
  ```
* **Mulai seluruh proses worker:**
  ```bash
  sudo supervisorctl start kampuslms-worker:*
  ```
* **Restart seluruh proses worker:**
  ```bash
  sudo supervisorctl restart kampuslms-worker:*
  ```
* **Periksa status proses secara langsung:**
  ```bash
  sudo supervisorctl status
  ```
* **Pantau keluaran log worker (*live stream*):**
  ```bash
  sudo supervisorctl tail -f kampuslms-worker:kampuslms-worker_00
  ```

### 3. Apa yang Terjadi Jika Kode Baru Di-deploy Tetapi `php artisan queue:restart` Lupa Dijalankan?
* **Penjelasan Teknis:**  
  Perintah `queue:work` adalah proses yang berjalan terus-menerus (*daemon long-lived process*). Saat pertama kali dinyalakan, worker memuat seluruh kode program Laravel, file konfigurasi, class Listener, class Event, dan class Notification ke dalam memori RAM server.
* **Dampak Kegagalan:**  
  Ketika pengembang memperbarui kode di server (misalnya mengubah teks template notifikasi, memperbaiki URL tautan tugas, atau mengubah payload database), worker yang sedang berjalan di RAM **tidak akan pernah membaca file baru dari disk penyimpanan**. Worker lama akan terus mengeksekusi logika kode versi usang yang tersimpan di memori. Akibatnya, perbaikan bug notifikasi tidak berlaku dan data notifikasi baru dapat mengalami anomali atau *fatal error* jika ada perubahan struktur model.
* **Solusi Kerja `queue:restart`:**  
  Perintah `php artisan queue:restart` menuliskan penanda waktu (*timestamp*) di cache server. Worker yang sedang berjalan akan menyelesaikan pekerjaan yang sedang ditanganinya saat ini, lalu keluar secara damai (*graceful shutdown*). Supervisor yang mendeteksi proses worker berhenti akan segera menyalakan instans worker baru yang secara otomatis memuat kode program terbaru dari disk ke dalam memori RAM.

### 4. Apa yang Terjadi pada Antrean Jika Service Supervisor Mati di Server Produksi, dan Bagaimana Cara Mendiagnosisnya?
* **Dampak pada Aplikasi:**  
  Jika service Supervisor mendadak mati (misal karena kehabisan memori server atau proses dimatikan paksa), **aplikasi web tidak akan menampilkan pesan error apa pun**. Dosen yang mempublikasikan tugas atau memberi nilai tetap mendapatkan respons HTTP 200/302 sukses karena request web hanya bertugas menyimpan data ke database dan memasukkan baris job ke tabel `jobs`.  
  Namun, antrean pekerjaan akan tertahan (*stuck*) di tabel `jobs`. Notifikasi tidak akan pernah diproses atau dipindahkan ke tabel `notifications`, dan mahasiswa tidak akan pernah menerima pemberitahuan lonceng (*Silent Failure*).
* **Prosedur Diagnosis Produksi (Tanpa Menyalakan `APP_DEBUG`):**
  1. **Periksa Jumlah Baris Tabel `jobs`:**
     Jalankan query database untuk melihat apakah ada pekerjaan yang menumpuk:
     ```bash
     php artisan tinker --execute="echo 'Job pending: ' . \DB::table('jobs')->count();"
     ```
     Jika angka ini lebih besar dari 0 dan tidak berkurang seiring waktu, dipastikan tidak ada worker yang memproses antrean.
  2. **Periksa Status Supervisor:**
     Jalankan perintah status daemon:
     ```bash
     sudo supervisorctl status
     ```
     Jika proses berstatus `STOPPED`, `FATAL`, atau daemon Supervisor tidak merespons (*refused connection*), berarti service Supervisor mati.
  3. **Periksa Log Supervisor dan Log Worker:**
     ```bash
     sudo tail -n 50 /var/log/supervisor/supervisord.log
     tail -n 50 /var/www/kampuslms-kelompok-06/storage/logs/worker.log
     ```
  4. **Periksa Tabel Pekerjaan Gagal (*Failed Jobs*):**
     Pastikan apakah kegagalan disebabkan oleh job yang mengalami exception:
     ```bash
     php artisan queue:failed
     ```
  5. **Tindakan Pemulihan:**
     Restart service Supervisor dan mulai ulang seluruh worker:
     ```bash
     sudo systemctl restart supervisor
     sudo supervisorctl start kampuslms-worker:*
     ```
     Seluruh job yang tertahan di tabel `jobs` akan langsung diproses secara otomatis oleh worker baru tanpa ada data yang hilang (*zero data loss*).


