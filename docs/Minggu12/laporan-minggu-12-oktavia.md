# Laporan Praktikum Minggu 12 — Deployment & Milestone M4 (Production Ready)
## Nama: Oktavia Nur Rahmadani
## NIM: 10241060
## Kelompok: 06 (EduKampus)
**Repositori:** KampusLMS (Laravel 12)  
**Dokumen Pendukung Utama:** [`docs/deployment.md`](../deployment.md) & [`scripts/deploy.sh`](../../scripts/deploy.sh)  
**Branch Git Kerja:** `Oktavia-Nur-12`  

---

## 1. Tahap 1: READ — Bedah Kesiapan Produksi & Quality Assurance (40 Menit)

Pada tahap awal penelaahan Modul 12 (Milestone M4: *Production Ready*), saya membedah komponen rilis, skrip otomasi deployment, dan penanganan akun demo evaluasi di server produksi sebelum aplikasi diuji secara menyeluruh.

### 1.1 Hasil Penelusuran 6 Poin Kunci READ Modul Minggu 12

1. **Struktur dan Peran Skrip Deployment Otomatis (`scripts/deploy.sh`):**  
   * **Analisis Kritis:** Skrip bash dirancang untuk mengeksekusi urutan pembaruan tanpa intervensi manual yang rentan salah (*human error*).
   * **Flag `set -e`:** Dipasang di baris paling atas untuk menerapkan prinsip *Fail-Fast*. Setiap perintah yang gagal akan langsung menghentikan proses deploy sehingga server tidak berada pada kondisi setengah terbarui (*half-deployed state*).
   * **Perlindungan Maintenance Mode:** `php artisan down --retry=60 || true` mengalihkan pengguna sementara ke halaman 503 saat migrasi dan build aset berlangsung, lalu dihidupkan kembali via `php artisan up` di akhir skrip.
2. **Karakteristik Lingkungan Produksi via `php artisan about`:**  
   * **Lokal:** `Environment: local`, `Debug Mode: ENABLED`, `Cache: NOT CACHED`.
   * **Produksi:** `Environment: production`, `Debug Mode: DISABLED (false)`, seluruh cache terkompilasi (`Config`, `Route`, `View`, `Event` berstatus `CACHED`).
3. **Penyusunan Akun Demo Produksi (`DemoAccountSeeder.php`):**  
   * Menggunakan pendekatan *idempotent* (cek `User::where('email', ...)->first()`, lalu update password atau buat baru jika belum ada).
   * Password dapat dikonfigurasi melalui `.env` produksi (`DEMO_PASSWORD`) dengan nilai default terenkripsi Bcrypt.
4. **Kebijakan Penyimpanan Kredensial Produksi:**  
   * Kredensial demo produksi dicatat secara tertib di [`docs/deployment.md`](../deployment.md) untuk keperluan tim penguji / evaluator.
   * Kredensial produksi **dilarang keras** dicantumkan di `README.md` publik guna mencegah kebocoran (*credential leakage*) dan akses ilegal oleh bot internet.
5. **Pengujian Responsivitas Antarmuka Mobile (Smartphone):**  
   * Seluruh komponen antarmuka (navbar, drawer menu, tabel rekap nilai, kartu materi perkuliahan) diuji menggunakan peramban HP untuk memastikan kepatuhan desain responsif dan kemudahan input sentuh (*touch target* $\ge 44 \times 44\text{ px}$).
6. **Troubleshooting & Pemantauan Log:**  
   * Saat `APP_DEBUG=false`, pelacakan eror dilakukan melalui analisis log server di `storage/logs/laravel.log` dan `storage/logs/worker.log`.

---

## 2. Tahap 2: BREAK — Tabel Eksperimen 8 Kerusakan Deployment (50 Menit)

> **Catatan Pengamanan Eksperimen:**  
> Eksperimen 1 dan 2 membuka informasi sensitif. Sesuai panduan modul, pengujian dibatasi menggunakan aturan IP Nginx lokal, dan konfigurasi server dinormalkan kembali seketika setelah observasi selesai.

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

Pada branch `w12` repositori evaluasi `kampuslms-broken`, diperbaiki **7 cacat konfigurasi deployment**:

1. **Pembersihan Berkas `.env.example`:**  
   * *Perbaikan:* Menghapus kredensial database riil dan rahasia aplikasi dari `.env.example`, menggantikannya dengan nilai placeholder aman.
2. **Pemasangan Flag `set -e` pada `deploy.sh`:**  
   * *Perbaikan:* Menambahkan `set -e` di baris atas skrip deploy agar proses otomatisasi berhenti seketika saat ada kegagalan eksekusi.
3. **Penyisipan Perintah `php artisan queue:restart`:**  
   * *Perbaikan:* Menambahkan `queue:restart` setelah migrasi dan pembuatan cache agar worker di RAM memuat kode terbaru.
4. **Koreksi Document Root Nginx:**  
   * *Perbaikan:* Mengarahkan konfigurasi root web server ke `/var/www/kampuslms-kelompok-06/public` (bukan ke folder induk proyek).
5. **Penegakan `APP_DEBUG=false` di Lingkungan Produksi:**  
   * *Perbaikan:* Mematikan mode debug secara permanen di `.env` produksi.
6. **Penghapusan Eksekusi `db:seed` Otomatis dari Alur Deployment Rutin:**  
   * *Perbaikan:* Menghilangkan perintah seeder dari `scripts/deploy.sh` agar tidak merusak atau menimpa data transaksi pengguna yang sudah berjalan.
7. **Penyertaan `.env` dalam `.gitignore`:**  
   * *Perbaikan:* Memastikan file `.env` tidak pernah terlacak oleh Git untuk mencegah kebocoran repositori.

---

## 4. Tahap 4: BUILD — Kontribusi Nyata Oktavia Nur Rahmadani (Milestone M4)

Sebagai penanggung jawab *Release Engineering*, *Quality Assurance*, dan *Troubleshooting Documentation*, berikut adalah kontribusi kunci yang telah saya selesaikan:

1. **Penyusunan & Pengujian Seeder Akun Demo Produksi ([`DemoAccountSeeder.php`](../../database/seeders/DemoAccountSeeder.php)):**  
   * Merancang seeder khusus akun demo evaluasi untuk 3 peran (Admin, Dosen, Mahasiswa).
   * Memastikan seeder bersifat *idempotent* dan terintegrasi aman dengan `DEMO_PASSWORD` di `.env`.
2. **Pengujian & Validasi Skrip Otomasi Deployment ([`scripts/deploy.sh`](../../scripts/deploy.sh)):**  
   * Memvalidasi setiap urutan: `artisan down` $\rightarrow$ `git reset --hard` $\rightarrow$ `composer install --no-dev` $\rightarrow$ `npm run build` $\rightarrow$ `migrate --force` $\rightarrow$ `cache optimization` $\rightarrow$ `queue:restart` $\rightarrow$ `artisan up`.
   * Memastikan penerapan flag `set -e` dan penanganan sinyal graceful restart worker berjalan sempurna.
3. **Penyusunan Log Troubleshooting Komprehensif di [`docs/deployment.md`](../deployment.md) Bagian 9:**  
   * Mendokumentasikan 8 kendala riil server produksi beserta gejala, akar masalah teknis, dan solusi penanganannya (masalah izin folder `storage/`, Vite assets 404, pemanggilan `env()` pasca cache, OOM Composer, dll.).
4. **Penyusunan & Eksekusi Checklist Pengujian Mobile Smartphone di [`docs/deployment.md`](../deployment.md) Bagian 10:**  
   * Melakukan pengujian fungsionalitas langsung dari browser HP (Chrome & Safari): verifikasi alur login multi-role, pengunduhan materi PDF, pengunggahan berkas tugas PDF, penilaian dosen, dan responsivitas navigasi layar sentuh.

---

## 5. Pertanyaan Checkpoint Minggu 12 (Persiapan Rekaman Layar M4)

Berikut adalah poin-poin jawaban yang saya gunakan dalam rekaman video interview individu Milestone M4:

* **T: Penguji akan menjalankan `curl -I https://domain/.env`. Mengapa hasilnya tidak boleh 200?**  
  * **J:** Jika respons HTTP bernilai 200, artinya file `.env` berhasil diunduh publik dan seluruh kredensial database, kunci rahasia aplikasi (`APP_KEY`), serta konfigurasi pihak ketiga bocor ke penyerang. Respons yang wajib dihasilkan adalah **404 Not Found** (karena document root Nginx mengarah tepat ke subfolder `/public`) atau **403 Forbidden** (karena diblokir oleh Nginx rule `location ~ /\.(?!well-known)`).
* **T: Apa bahaya konkret `APP_DEBUG=true` di server produksi?**  
  * **J:** Menyalakan debug mode di produksi akan menampilkan halaman Ignition/Whoops lengkap saat terjadi kesalahan. Halaman ini membocorkan baris source code, variabel lingkungan, struktur database, cookie sesi, dan path absolut server yang membuka peluang eksploitasi fatal.
* **T: Mengapa document root harus mengarah ke `/public`, bukan ke root repositori?**  
  * **J:** Folder root proyek menyimpan file sensitif (`.env`, `composer.json`, `storage/logs`, source code). Mengarahkan root Nginx ke `/public` menjamin hanya berkas aset yang memang diperuntukkan bagi publik yang dapat diakses dari internet.
* **T: Mengapa `queue:restart` wajib setelah setiap deploy?**  
  * **J:** Proses worker Laravel bersifat *long-running daemon* yang memuat kode ke memori RAM saat pertama kali dijalankan. Jika tidak di-restart pasca deploy, worker akan terus menjalankan logika lama yang tersimpan di RAM dan mengabaikan kode baru yang baru saja diunduh dari Git.
