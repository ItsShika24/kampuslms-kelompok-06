# Laporan Praktikum Minggu 12 — Deployment & Milestone M4 (Production Ready)
## Nama: Tika Mila Wahyuni
## NIM: 10241070
## Kelompok: 06 (EduKampus)
**Repositori:** KampusLMS (Laravel 12)  
**Dokumen Pendukung Utama:** [`docs/deployment.md`](../deployment.md) & [`scripts/nginx-kampuslms.conf`](../../scripts/nginx-kampuslms.conf)  

---

## 1. Tahap 1: READ — Bedah Server Produksi (40 Menit)

Pada tahap awal pengerjaan deployment, dilakukan penelaahan mendalam terhadap infrastruktur server Ubuntu dan arsitektur web server Nginx sebelum aplikasi diekspos ke domain publik.

### 1.1 Hasil Penelusuran 6 Poin READ Modul Minggu 12

1. **Konfigurasi Nginx dan Penentuan Document Root:**  
   * **Baris Penentu:** Direktif `root /var/www/kampuslms-kelompok-06/public;` pada blok server Nginx.
   * **Analisis Kritis:** Direktif ini mengarahkan Nginx secara spesifik ke subfolder `/public`. Dengan demikian, file inti aplikasi seperti `.env`, `composer.json`, `package.json`, dan direktori `storage/` berada di satu tingkat di luar document root (*above document root*) sehingga tidak dapat diakses langsung oleh peramban internet.
2. **Perbandingan Hasil `php artisan about` (Lokal vs Produksi):**  
   * **Lokal:** `Environment: local`, `Debug Mode: ENABLED`, `URL: http://localhost:8000`, `Config Cache: NOT CACHED`, `Route Cache: NOT CACHED`.
   * **Produksi:** `Environment: production`, `Debug Mode: DISABLED (false)`, `URL: https://kampuslms-kelompok-06.proweb.my.id`, `Config Cache: CACHED`, `Route Cache: CACHED`, `View Cache: CACHED`, `Event Cache: CACHED`.
3. **Pemeriksaan Status Supervisor:**  
   * Menjalankan perintah `sudo supervisorctl status` menunjukkan 2 proses antrean aktif:
     ```text
     kampuslms-worker:kampuslms-worker_00   RUNNING   pid 14201, uptime 0:45:12
     kampuslms-worker:kampuslms-worker_01   RUNNING   pid 14202, uptime 0:45:12
     ```
4. **Pemeriksaan Berkas `storage/logs/laravel.log`:**  
   * Log berukuran bersih tanpa *stack trace* eror kritis. Saat terjadi kesalahan pada `APP_DEBUG=false`, seluruh pesan kesalahan internal secara aman tercatat di sini dan tidak terekspos ke layar pengguna.
5. **Eksekusi Pengujian Audit Akses Berkas Sensitif (`curl -I`):**  
   * `curl -I https://domain/.env` ➔ **403 Forbidden** (diblokir oleh Nginx rule `location ~ /\.(?!well-known)`).
   * `curl -I https://domain/storage/logs/laravel.log` ➔ **404 Not Found** (berada di luar document root).
   * `curl -I https://domain/composer.json` ➔ **404 Not Found** (tidak dapat dijangkau).
6. **Pengujian Tampilan via Smartphone:**  
   * Tampilan antarmuka Tailwind CSS responsif, form login dan tabel buku rekap nilai dapat diakses sempurna melalui browser ponsel tanpa tampilan rusak.

---

## 2. Tahap 2: BREAK — Tabel Eksperimen 8 Kerusakan Deployment (50 Menit)

> **Catatan Pengamanan Eksperimen:**  
> Eksperimen 1 dan 2 sengaja membuka informasi rahasia. Sesuai instruksi modul, pengujian dilakukan di balik pembatasan alamat IP pada blok Nginx (`allow <ip-lokal>; deny all;`) dan **segera dinormalkan kembali** setelah observasi selesai. Kredensial database diganti dan `php artisan key:generate` dijalankan ulang pasca-percobaan.

Berikut adalah tabel hasil pengamatan 8 percobaan kerusakan deployment:

| # | Percobaan Kerusakan (*Action*) | Prediksi Sebelum Mencoba | Hasil Pengamatan Aktual | Bahaya / Pelajaran Utama & Mitigasi |
|:---:|---|---|---|---|
| **1** | Set `APP_DEBUG=true`, picu error form di server publik | Halaman error Laravel akan menampilkan detail exception dan variabel request. | **Kebocoran Fatal:** Layar menampilkan cuplikan source code, path folder server `/var/www/...`, database credentials di stack trace, cookie session, dan isi request body. | **Diskualifikasi Otomatis:** `APP_DEBUG=false` wajib aktif sejak menit pertama deployment di server publik. |
| **2** | Arahkan document root Nginx ke root proyek (`/var/www/...`), hapus blokir file titik | Siapa pun dapat mengakses dan mengunduh berkas `.env` langsung lewat browser. | **Seluruh Rahasia Terunduh:** Mengakses `https://domain/.env` langsung menampilkan seluruh isi database password dan app key dalam teks polos (HTTP 200). | Document root WAJIB ke subfolder `/public`. Blokir Nginx `location ~ /\.` bertindak sebagai lapisan pertahanan ganda. |
| **3** | Jalankan `config:cache` sementara masih ada `env()` di controller | Fungsi `env()` akan mengembalikan nilai `null`. | Controller yang memanggil `env('APP_NAME')` menghasilkan string kosong / `null` sehingga tampilan layout rusak. | Setelah cache aktif, file `.env` tidak lagi dibaca. Wajib menggunakan helper `config('...')`. |
| **4** | Hentikan service Supervisor (`supervisorctl stop all`), buat tugas baru | Tugas tersimpan di database, tetapi notifikasi ke mahasiswa tidak pernah terkirim. | **Silent Failure:** Web dosen merespons sukses tanpa error, namun baris pekerjaan tertahan di tabel `jobs` dan tabel `notifications` kosong. | Antrean latar belakang membutuhkan daemon yang berjalan konstan. Supervisor wajib dikonfigurasi auto-start. |
| **5** | Deploy kode baru di server tanpa menjalankan `queue:restart` | Background worker tetap memproses data menggunakan kode versi lama. | Worker memproses event notifikasi dengan template pesan lama yang sudah usang karena worker lama masih tertahan di RAM. | Worker Laravel memuat kode ke memori saat boot. `php artisan queue:restart` wajib dieksekusi setiap deploy. |
| **6** | Ubah hak akses folder `storage` menjadi `chmod 555` | Aplikasi akan mengalami error 500 karena tidak dapat menulis file log dan cache view. | Muncul error HTTP 500 kosong, dan bahkan file `laravel.log` tidak dapat mencatat error tersebut (*permission denied*). | Web server `www-data` wajib memiliki hak tulis (`chmod 775` dan `chown www-data:www-data`). |
| **7** | Atur `upload_max_filesize=1M` di `php.ini`, unggah berkas PDF 3 MB | Unggahan akan ditolak oleh server PHP sebelum validasi Form Request Laravel berjalan. | Browser menerima error *The uploaded file exceeds the upload_max_filesize directive in php.ini* atau koneksi reset. | Batasan upload di `php.ini` (`upload_max_filesize` & `post_max_size`) dan Nginx (`client_max_body_size`) wajib lebih besar dari validasi aplikasi (20 MB). |
| **8** | Buka website via `http://` setelah `SESSION_SECURE_COOKIE=true` | Cookie session tidak akan dikirim oleh browser karena koneksi tidak aman. | Pengguna tidak pernah bisa login; setiap kali submit login, pengguna kembali diarahkan ke form login karena session hilang. | Flag `Secure` pada cookie mewajibkan jalur HTTPS. Nginx wajib me-redirect seluruh port 80 ke 443. |

---

## 3. Tahap 3: FIX — Perbaikan Repo Cacat `kampuslms-broken` Branch `w12` (30 Menit)

Pada branch `w12` repositori `kampuslms-broken`, ditemukan dan diperbaiki **7 masalah konfigurasi deployment**:

1. **`.env.example` Memuat Kredensial Nyata:**  
   * *Perbaikan:* Menghapus password database dan kunci API nyata dari `.env.example`, menggantinya dengan nilai *dummy/placeholder*.
2. **Skrip `deploy.sh` Tanpa `set -e`:**  
   * *Perbaikan:* Menambahkan `set -e` di baris atas skrip agar proses berhenti seketika jika ada perintah yang gagal (mencegah deployment setengah jadi).
3. **Ketiadaan `php artisan queue:restart` pada Skrip Deploy:**  
   * *Perbaikan:* Menyisipkan perintah `php artisan queue:restart` setelah migrasi dan cache dibangun ulang.
4. **Konfigurasi Nginx Mengarah ke Root Repositori:**  
   * *Perbaikan:* Mengubah direktif `root /var/www/kampuslms;` menjadi `root /var/www/kampuslms/public;`.
5. **Nilai `APP_DEBUG=true` di File Template Produksi:**  
   * *Perbaikan:* Mengubah default menjadi `APP_DEBUG=false` dan `APP_ENV=production`.
6. **Perintah `db:seed` Dijalankan Otomatis di Setiap Deployment:**  
   * *Perbaikan:* Menghapus `db:seed` dari alur deploy rutin agar tidak menimpa data transaksi nyata pengguna di server produksi.
7. **File `.env` Tidak Masuk `.gitignore`:**  
   * *Perbaikan:* Memastikan baris `.env` terdaftar di `.gitignore` untuk mencegah kebocoran kunci rahasia ke publik.

---

## 4. Tahap 4: BUILD — Kontribusi Nyata Tika Mila Wahyuni (Milestone M4)

Sebagai penanggung jawab infrastruktur server dan web server Nginx pada kelompok 06, berikut adalah deliverable teknis yang telah saya selesaikan:

1. **Penyusunan Berkas Konfigurasi Nginx Produksi:**  
   * Membuat template resmi di [`scripts/nginx-kampuslms.conf`](../../scripts/nginx-kampuslms.conf) yang memuat konfigurasi SSL HTTPS, pemblokiran berkas titik (`.env`), batas upload body `client_max_body_size 25M`, dan integrasi PHP 8.4-FPM.
2. **Penyusunan Panduan Lengkap di [`docs/deployment.md`](../deployment.md):**  
   * Menuliskan Bagian 1 (Tabel Spesifikasi Server Produksi).
   * Menuliskan Bagian 2 (Panduan Instalasi Lengkap dari Nol / *Fresh Ubuntu Setup*).
   * Menuliskan Bagian 3 (Konfigurasi Nginx Virtual Host).
   * Menuliskan Bagian 8 (Panduan Pemantauan & Pembacaan Log Server).
3. **Penyusunan Skrip Inisialisasi Hak Akses Direktori:**  
   * Memastikan folder `storage/` dan `bootstrap/cache/` memiliki izin `775` dengan kepemilikan `www-data:www-data` sehingga aplikasi bebas dari error 500 izin berkas.
4. **Verifikasi Kesiapan Awal Aplikasi:**  
   * Menjamin seluruh cache performa (`config:cache`, `route:cache`, `view:cache`, `event:cache`) dan symlink storage (`php artisan storage:link`) telah siap dieksekusi secara mulus pada deployment perdana.

---

## 5. Pertanyaan Checkpoint Minggu 12 (Persiapan Rekaman Layar M4)

Berikut adalah ringkasan jawaban untuk materi rekaman video interview individu Milestone M4 saya:

* **T: Penguji akan menjalankan `curl -I https://domain/.env`. Mengapa hasilnya tidak boleh 200?**  
  * **J:** Jika mengembalikan 200, berarti berkas `.env` berhasil diunduh publik dan kredensial database serta `APP_KEY` bocor. Hasil yang benar adalah **404 Not Found** (karena document root mengarah ke `/public`) atau **403 Forbidden** (karena aturan blokir Nginx `location ~ /\.`).
* **T: Apa bahaya konkret `APP_DEBUG=true` di server produksi?**  
  * **J:** Jika terjadi eror, Laravel akan membocorkan cuplikan baris kode, path internal server, nilai variabel session/cookie, dan query database di layar browser yang dapat dimanfaatkan penyerang untuk mengeksploitasi sistem.
* **T: Mengapa document root harus mengarah ke `/public`?**  
  * **J:** Hanya folder `/public` yang dirancang untuk melayani aset web (`index.php`, CSS, JS). Folder aplikasi lainnya berisi logika rahasia yang tidak boleh terekspos langsung.
* **T: Mengapa `queue:restart` wajib setelah setiap deploy?**  
  * **J:** Worker antrean menyimpan kode aplikasi di memori RAM. Tanpa perintah restart, worker lama akan terus menjalankan kode versi lama meskipun source code di server sudah di-update.
