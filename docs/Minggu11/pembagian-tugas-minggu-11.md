# PANDUAN & PEMBAGIAN TUGAS MINGGU 11 — OPTIMISASI PERFORMA
## Proyek: KampusLMS (EduKampus) — Laravel 12
**Mata Kuliah:** SI2514024 Pemrograman Web (3 SKS)  
**Kelompok:** Kelompok 06 (Kelas A/B)  
**Target Akhir:** Mengukur, mendiagnosis, mengoptimasi query N+1, membuat indeks database, menerapkan caching, dan menyusun laporan pengukuran sebelum-sesudah di `docs/performa.md`.

> ⚠️ **PENTING UNTUK SELURUH ANGGOTA:**  
> Minggu ini **tidak ada penambahan fitur baru**. Seluruh anggota bekerja untuk mengoptimalkan kode yang sudah ada. Setiap anggota wajib memiliki commit atas namanya sendiri via Pull Request (PR) sesuai peran masing-masing.

---

## 👥 Ringkasan Pembagian Peran Anggota

| No | Anggota | NIM | Fokus Tanggung Jawab Utama | Deliverable Kunci |
|:---:|---|:---:|---|---|
| 1 | **Raihandy Wijaya** | 10241064 | Optimasi REST API v1 & Pembersihan `env()` Liar | Eager loading API Resources, sanitasi `env()` ke `config()`, uji `config:cache` |
| 2 | **Tika Mila Wahyuni** | 10241070 | Deteksi N+1 Global & Migrasi Indeks Database | `Model::preventLazyLoading()`, file migrasi indeks baru, pembuktian `EXPLAIN` |
| 3 | **Shabrina Putri Azzahra** | 10241066 | Optimasi Query Rekap Nilai & Caching Dashboard | Query `/grades` < 15 query, `withCount()`, `Cache::remember` + auto-invalidation |
| 4 | **Oktavia Nur Rahmadani** | 10241060 | Pengukuran Debugbar, Optimasi Blade, `docs/performa.md` | Pemasangan Debugbar, optimasi view (`loading="lazy"`), laporan lengkap `docs/performa.md` |

---

## 📌 Rincian Tugas, File Konteks, dan Prompt AI per Anggota

---

### 1. Raihandy Wijaya (Fullstack & REST API)
**Peran:** Fullstack & REST API  
**Branch Git Rekomendasi:** `feat/optimasi-api-config-raihandy`

#### A. Tugas Spesifik:
1. **Audit Seluruh Controller API di `app/Http/Controllers/Api/`:**
   - Pastikan endpoint daftar (koleksi) sudah menggunakan eager loading (`with()`, `withCount()`) untuk mencegah N+1 saat API dikonsumsi klien frontend / mobile.
   - Periksa API Resources di `app/Http/Resources/`: gunakan method `whenLoaded()` untuk relasi agar relasi tidak di-query secara diam-diam (*lazy load*) jika tidak diminta.
   - Pastikan seluruh endpoint koleksi tetap mematuhi pagination (15 item/halaman).
2. **Audit dan Pembersihan Pemanggilan `env()` Liar (Sangat Kritis untuk Minggu 12):**
   - Cari seluruh fungsi `env(...)` di folder `app/` dan `resources/views/`.
   - Ganti seluruh panggilan `env('KEY', 'default')` menjadi `config('app.key')`.
   - *Alasan:* Saat `php artisan config:cache` dijalankan di server produksi nanti, file `.env` tidak lagi dibaca dan semua panggilan `env()` di luar folder `config/` akan mengembalikan `null`!
3. **Uji Optimasi Produksi:**
   - Jalankan perintah:
     ```bash
     php artisan config:cache
     php artisan route:cache
     php artisan view:cache
     ```
   - Pastikan aplikasi web dan API tetap berfungsi normal tanpa ada nilai konfigurasi yang hilang/null.

#### B. File Repositori yang Diberikan ke AI Sebagai Konteks:
Lampirkan file-file berikut saat meminta bantuan ke AI:
* `routes/api.php`
* `app/Http/Controllers/Api/CourseController.php`
* `app/Http/Controllers/Api/AssignmentController.php`
* `app/Http/Controllers/Api/SubmissionController.php`
* `app/Http/Resources/CourseResource.php`
* `app/Http/Resources/AssignmentResource.php`
* `app/Http/Resources/SubmissionResource.php`

#### C. Prompt AI Siap Pakai untuk Raihandy:

```text
Halo, saya Raihandy Wijaya dari Kelompok 06 tugas Pemrograman Web (Laravel 12).
Tugas saya di Minggu 11 adalah mengoptimasi performa REST API v1 dan membersihkan pemanggilan env() liar sebelum deployment.

Berikut adalah kode controller dan API resource yang saya lampirkan:
[Lampirkan isi file: routes/api.php, CourseController.php API, dan API Resource terkait]

Tolong bantu saya:
1. Analisis apakah ada endpoint di API saya yang berpotensi memicu query N+1 saat mengembalikan daftar data.
2. Tunjukkan cara menerapkan 'whenLoaded()' pada CourseResource, AssignmentResource, dan SubmissionResource agar relasi hanya disertakan saat sudah di-eager load oleh controller.
3. Buatkan skrip terminal / perintah grep untuk mendeteksi semua pemanggilan fungsi env() di luar folder config/. Berikan contoh cara mengubahnya menjadi config('...').
4. Jelaskan apa yang terjadi pada fungsi env() setelah perintah 'php artisan config:cache' dijalankan, dan mengapa ini menjadi penyebab umum crash di server produksi.
```

---

### 2. Tika Mila Wahyuni (Backend & Security)
**Peran:** Backend & Security  
**Branch Git Rekomendasi:** `feat/database-indexing-tika`

#### A. Tugas Spesifik:
1. **Mengaktifkan `Model::preventLazyLoading()` Secara Global:**
   - Buka `app/Providers/AppServiceProvider.php`.
   - Di method `boot()`, tambahkan:
     ```php
     Model::preventLazyLoading(! app()->isProduction());
     ```
   - Uji coba klik seluruh menu di browser (Mata Kuliah, Tugas, Submission, Notifikasi, Rekap Nilai). Jika muncul exception `LazyLoadingViolationException`, catat file controller dan baris kodenya untuk diperbaiki bersama tim.
2. **Merancang dan Membuat File Migrasi Indeks Database Baru:**
   - Analisis kolom-kolom yang sering muncul di klausa `WHERE`, filter status, atau `ORDER BY` pada query aplikasi.
   - Buat migrasi baru:
     ```bash
     php artisan make:migration add_performance_indexes_to_tables
     ```
   - Tambahkan indeks pada kolom yang tepat (contoh: `status` pada `assignments`, `submitted_at` pada `submissions`, dsb.).
   - Tuliskan komentar pada migrasi yang menjelaskan query mana yang diuntungkan oleh setiap indeks.
   - Pastikan method `down()` benar-benar menghapus indeks yang dibuat (`dropIndex(...)`).
3. **Membuktikan Efektivitas Indeks dengan `EXPLAIN`:**
   - Buka terminal MySQL/DBMS dan jalankan `EXPLAIN` pada salah satu query sebelum dan sesudah indeks dipasang.
   - Catat perubahan nilai kolom `type` (dari `ALL` menjadi `ref` / `range`) dan penurunan jumlah baris yang dipindai (`rows`).

#### B. File Repositori yang Diberikan ke AI Sebagai Konteks:
Lampirkan file-file berikut saat meminta bantuan ke AI:
* `app/Providers/AppServiceProvider.php`
* `database/migrations/2026_09_14_090603_create_courses_table.php`
* `database/migrations/2026_09_14_092556_create_assignments_table.php`
* `database/migrations/2026_09_14_092829_create_submissions_table.php`
* `database/migrations/2026_09_14_093005_create_grades_table.php`

#### C. Prompt AI Siap Pakai untuk Tika:

```text
Halo, saya Tika Mila Wahyuni dari Kelompok 06 tugas Pemrograman Web (Laravel 12).
Tugas saya di Minggu 11 adalah mengaktifkan proteksi Model::preventLazyLoading() dan merancang file migrasi penambahan indeks database MySQL untuk optimasi query.

Berikut skema tabel migrasi aplikasi kami:
[Lampirkan isi file migrasi courses, assignments, submissions, dan grades]

Tolong bantu saya:
1. Tunjukkan cara mendaftarkan Model::preventLazyLoading(! app()->isProduction()) di AppServiceProvider.php Laravel 12 dengan benar.
2. Berdasarkan skema tabel dan fitur aplikasi LMS kami (filter tugas, pengurutan submission, status nilai), rancang file migrasi baru 'add_performance_indexes_to_tables'.
3. Untuk setiap indeks yang ditambahkan, jelaskan query apa yang diuntungkan dan apa dampak negatifnya (biaya penulisan/insert).
4. Berikan contoh perintah kueri SQL 'EXPLAIN SELECT ...' sebelum dan sesudah indeks dipasang, serta jelaskan cara membaca kolom 'type' dan 'rows' untuk laporan praktikum saya.
```

---

### 3. Shabrina Putri Azzahra (Database & Testing / Backend Logic)
**Peran:** Database & Testing  
**Branch Git Rekomendasi:** `feat/optimasi-rekap-cache-shabrina`

#### A. Tugas Spesifik:
1. **Optimasi Berat pada Halaman Rekap Nilai (`GradeController.php`):**
   - Halaman Rekap Nilai (`/grades`) adalah halaman yang paling rentan terhadap query N+1 karena merelasikan Course → Assignments → Submissions → Students → Grade.
   - Target modul: Jumlah query di halaman rekap nilai harus turun menjadi **di bawah 15 query**!
   - Perbaiki pemanggilan relasi dengan eager loading bertingkat:
     ```php
     Course::with([
         'assignments' => fn ($q) => $q->orderBy('due_at'),
         'assignments.submissions.grade',
         'assignments.submissions.student:id,name,nim_nip',
         'students:id,name,nim_nip'
     ])
     ```
   - Batasi kolom dengan `select('id', 'name', 'nim_nip')` untuk menghemat alokasi memori RAM PHP.
2. **Optimasi Penghitungan Statistik Dashboard (`DashboardController.php`):**
   - Hapus semua pemanggilan yang memuat seluruh relasi ke memori hanya untuk menghitung jumlahnya (seperti `count($course->students)`).
   - Ganti dengan `withCount(['students', 'assignments'])`.
3. **Implementasi Caching Statistik Dashboard & Cache Invalidation:**
   - Terapkan `Cache::remember("dashboard.stats.user.{$user->id}", now()->addMinutes(10), ...)` untuk ringkasan angka di dashboard dosen dan admin.
   - Pasang pembersihan cache otomatis (*cache invalidation*) pada event lifecycle model `Assignment.php` dan `Submission.php`:
     ```php
     protected static function booted(): void
     {
         static::saved(fn ($model) => Cache::forget(...));
         static::deleted(fn ($model) => Cache::forget(...));
     }
     ```
   - ⚠️ **Catatan Penting:** Dilarang me-cache nilai submission mahasiswa karena data nilai harus diperbarui secara instan.

#### B. File Repositori yang Diberikan ke AI Sebagai Konteks:
Lampirkan file-file berikut saat meminta bantuan ke AI:
* `app/Http/Controllers/GradeController.php`
* `app/Http/Controllers/DashboardController.php`
* `app/Models/Assignment.php`
* `app/Models/Submission.php`
* `resources/views/grades/index.blade.php`

#### C. Prompt AI Siap Pakai untuk Shabrina:

```text
Halo, saya Shabrina Putri Azzahra dari Kelompok 06 tugas Pemrograman Web (Laravel 12).
Tugas saya di Minggu 11 adalah mengoptimasi query halaman Buku Rekap Nilai agar turun di bawah 15 query, serta menerapkan Caching pada ringkasan statistik Dashboard.

Berikut kode GradeController, DashboardController, dan view rekap nilai saya:
[Lampirkan isi file: GradeController.php, DashboardController.php, dan grades/index.blade.php]

Tolong bantu saya:
1. Analisis alur kueri pada GradeController dan view grades/index.blade.php. Di mana letak potensi N+1 query yang membuat kueri membengkak saat menampilkan matriks nilai mahasiswa?
2. Berikan kode perbaikan GradeController.php dengan eager loading bertingkat dan pembatasan select kolom agar total kueri di bawah 15 kueri.
3. Tunjukkan cara mengganti pemanggilan count() boros memori di DashboardController menjadi withCount().
4. Tunjukkan implementasi Cache::remember untuk data statistik dashboard dan mekanisme pembersihan cache (cache invalidation) saat ada tugas baru dibuat.
```

---

### 4. Oktavia Nur Rahmadani (Frontend & Form Validation / Dokumentasi UX)
**Peran:** Frontend & Form Validation  
**Branch Git Rekomendasi:** `docs/laporan-performa-oktavia`

#### A. Tugas Spesifik:
1. **Pemasangan & Pengukuran Menggunakan Laravel Debugbar:**
   - Jalankan instalasi debugbar di lokal:
     ```bash
     composer require barryvdh/laravel-debugbar --dev
     ```
   - Lakukan pengukuran **SEBELUM optimisasi** (baseline) pada data seeder penuh untuk 7 halaman utama:
     - Daftar Mata Kuliah (`/mata-kuliah`)
     - Detail Mata Kuliah (`/mata-kuliah/1`)
     - Detail Tugas & Form Submit (`/tugas/1`)
     - Pusat Penilaian Tugas Dosen (`/pengumpulan-tugas`)
     - Buku Rekap Nilai (`/grades`)
     - Dashboard Mahasiswa (`/dashboard`)
     - Dashboard Dosen (`/dashboard`)
   - Catat 3 parameter dari bar bawah Debugbar: **Jumlah Queries**, **Waktu Respons (ms)**, dan **Alokasi Memori (MB)**.
2. **Penyusunan Dokumen Resmi `docs/performa.md`:**
   - Buat berkas baru di `docs/performa.md` sesuai format standar modul Minggu 11.
   - Sajikan tabel perbandingan Sebelum vs Sesudah optimisasi.
   - Cantumkan analisis query terlambat beserta tangkapan layar / bukti perintah `EXPLAIN`.
   - Dokumentasikan bagian mana yang di-cache dan beri penjelasan mengapa nilai mahasiswa sengaja **tidak** di-cache.
3. **Audit dan Optimasi Template Blade:**
   - Periksa seluruh tag gambar (`<img>`): pastikan memiliki atribut `loading="lazy"`.
   - Pastikan tidak ada query database langsung di dalam file `.blade.php` (misal `@foreach(Course::all() ...)`). Seluruh data wajib dikirim dari controller.
   - Pastikan link pagination di view mempertahankan query string filter (`{{ $items->withQueryString()->links() }}`).

#### B. File Repositori yang Diberikan ke AI Sebagai Konteks:
Lampirkan file-file berikut saat meminta bantuan ke AI:
* `resources/views/grades/index.blade.php`
* `resources/views/dashboard.blade.php`
* `resources/views/courses/index.blade.php`
* `resources/views/submissions/index.blade.php`
* `resources/views/components/layout.blade.php`

#### C. Prompt AI Siap Pakai untuk Oktavia:

```text
Halo, saya Oktavia Nur Rahmadani dari Kelompok 06 tugas Pemrograman Web (Laravel 12).
Tugas saya di Minggu 11 adalah mengukur metrik performa aplikasi dengan Laravel Debugbar, mengaudit template Blade, dan menyusun dokumen resmi 'docs/performa.md'.

Berikut adalah struktur view dan informasi halaman yang kami ukur:
[Lampirkan isi file: resources/views/grades/index.blade.php dan dashboard.blade.php]

Tolong bantu saya:
1. Susunkan kerangka/template dokumen markdown 'docs/performa.md' yang rapi dan sesuai dengan rubrik Modul Minggu 11.
2. Buatkan tabel perbandingan performa (Jumlah Query, Waktu Eksekusi ms, Penggunaan Memori MB) sebelum vs sesudah optimisasi.
3. Bantu saya menuliskan penjelasan konseptual di dokumen: Mengapa data statistik dashboard layak di-cache, sedangkan nilai dan submission mahasiswa DILARANG KERAS di-cache?
4. Periksa template Blade yang saya lampirkan: apakah ada pemanggilan relasi atau logika query yang memicu N+1 di view, dan bagaimana memastikan gambar menggunakan loading="lazy"?
```

---

## 📅 Alur & Urutan Pengerjaan Bersama (Checklist Minggu 11)

```
[Tahap 1: Oktavia] ──> Catat baseline awal seluruh halaman di Debugbar (Simpan ke docs/performa.md)
       │
[Tahap 2: Tika]    ──> Pasang Model::preventLazyLoading() & Buat migrasi indeks database
       │
[Tahap 3: Shabrina]──> Optimasi GradeController (turunkan < 15 query) & Cache statistik dashboard
       │
[Tahap 4: Raihandy]──> Optimasi REST API v1 (whenLoaded) & Hapus seluruh fungsi env() di luar config/
       │
[Tahap 5: Oktavia] ──> Ukur ulang semua halaman di Debugbar, lengkapi angka akhir di docs/performa.md
       │
[Tahap 6: Bersama] ──> Review Pull Request masing-masing di GitHub, pastikan CI Actions tetap HIJAU!
```

---

## 🎯 Pertanyaan Checkpoint Minggu 11 (Persiapan Interview)

Setiap anggota wajib memahami jawaban dari pertanyaan berikut sebelum sesi praktikum berakhir:
1. **Halaman mana yang paling boros query sebelum dioptimasi? Mengapa?**  
   *Jawaban:* Halaman Rekap Nilai (`/grades`), karena menampilkan matriks relasi berlapis antara Mata Kuliah → Seluruh Tugas → Seluruh Mahasiswa → Seluruh Submission → Nilai.
2. **Apa perbedaan mendasar antara `count($course->students)` dan `$course->withCount('students')`?**  
   *Jawaban:* `count($course->students)` melakukan query `SELECT *` yang memuat ratusan objek User ke memori RAM server lalu dihitung dengan fungsi PHP. Sedangkan `withCount('students')` dieksekusi langsung di MySQL menggunakan `COUNT(*)` via subquery tanpa memuat objek ke memori.
3. **Apa akibat memanggil fungsi `env()` jika `php artisan config:cache` sudah dijalankan?**  
   *Jawaban:* Fungsi `env()` akan mengembalikan nilai `null`, karena setelah di-cache Laravel tidak lagi memuat file `.env`. Pemanggilan wajib menggunakan `config('nama_konfigurasi')`.
4. **Data apa yang tidak boleh di-cache? Mengapa?**  
   *Jawaban:* Nilai mahasiswa dan status submission tugas. Caching akan menyebabkan data basi (*stale data*), sehingga mahasiswa tidak bisa melihat nilai terkini yang baru saja diinput oleh dosen.

