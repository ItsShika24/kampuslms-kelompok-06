# Laporan Praktikum Minggu 11 — Optimisasi Performa
## Nama: Shabrina Putri Az-Zahra
## NIM: 10241066
## Kelompok: Kelompok 06 (EduKampus)
**Peran Utama:** Database & Testing / Backend Logic  
**Repositori:** KampusLMS (Laravel 12)  
**Dokumen Master Performa:** [`docs/performa.md`](../performa.md)  

---

## 1. Tahap 1: READ — Pengukuran Baseline & Profiling Kueri Aplikasi (40 Menit)

Sesuai instruksi Modul Minggu 11.1 dan 11.3, proses optimasi performa harus diawali dengan pengukuran kuantitatif (*profiling*), bukan berdasarkan dugaan (*"jangan menebak"*). Sebelum profiling dijalankan, dataset realistis di-generate menggunakan perintah:

```bash
php artisan migrate:fresh --seed
```

Dataset memuat volume yang representatif: **30 mahasiswa terdaftar, 3 dosen, 1 admin, 5 mata kuliah, 15 tugas, 105 berkas submission pengumpulan tugas, dan 60 catatan nilai (grades)**. Alat ukur yang digunakan adalah **Laravel Debugbar** dan **Laravel Telescope** di lingkungan lokal (*PHP 8.5 / MySQL 8.0*).

### 1.1 Tabel Pengukuran 7 Halaman Utama (Kondisi Awal / Baseline)

Berikut adalah tabel pengukuran awal pada 7 halaman utama aplikasi KampusLMS sebelum optimasi kueri dan caching:

| Halaman | Jumlah Query | Waktu Respons (ms) | Penggunaan Memori (MB) |
|---|:---:|:---:|:---:|
| **Daftar mata kuliah** (`/courses`) | 5 query | 42 ms | 4.5 MB |
| **Detail mata kuliah** (`/courses/{id}`) | 7 query | 68 ms | 5.1 MB |
| **Daftar tugas** (`/assignments`) | 4 query | 35 ms | 4.2 MB |
| **Daftar pengumpulan tugas dosen** (`/submissions`) | 9 query | 88 ms | 5.6 MB |
| **Rekap nilai** (`/grades`) | **87 query** | **1.380 ms** | **21.4 MB** |
| **Dashboard mahasiswa** (`/dashboard`) | 16 query | 210 ms | 6.5 MB |
| **Dashboard dosen** (`/dashboard`) | 24 query | 340 ms | 8.2 MB |

---

### 1.2 Urutan Halaman dari yang Terburuk

Berdasarkan ketiga metrik (jumlah kueri, waktu respons, dan konsumsi memori RAM), urutan performa dari yang paling buruk ke paling efisien adalah:

1. **Peringkat 1 (Paling Kritis): Rekap Nilai (`/grades`)**  
   * **87 query | 1.380 ms (1,38 detik) | 21.4 MB RAM**.  
   * Halaman ini mengeksekusi puluhan kueri SQL berulang di dalam matriks tabel per mahasiswa per tugas (*nested loop*).
2. **Peringkat 2: Dashboard Dosen (`/dashboard`)**  
   * **24 query | 340 ms | 8.2 MB RAM**.  
   * Memuat seluruh model koleksi mahasiswa ke memori PHP hanya demi menghitung jumlah mahasiswa bimbingan (`$myCourses->sum(fn($c) => $c->students->count())`).
3. **Peringkat 3: Dashboard Mahasiswa (`/dashboard`)**  
   * **16 query | 210 ms | 6.5 MB RAM**.  
   * Loop `foreach` di Blade mengeksekusi kueri terpisah per mata kuliah untuk menghitung tugas berstatus aktif.
4. **Peringkat 4: Pusat Penilaian / Pengumpulan Tugas (`/submissions`)**  
   * **9 query | 88 ms | 5.6 MB RAM**.
5. **Peringkat 5: Detail Mata Kuliah (`/courses/{id}`)**  
   * **7 query | 68 ms | 5.1 MB RAM**.
6. **Peringkat 6: Daftar Mata Kuliah (`/courses`)**  
   * **5 query | 42 ms | 4.5 MB RAM**.
7. **Peringkat 7 (Paling Ringan): Daftar Tugas Perkuliahan (`/assignments`)**  
   * **4 query | 35 ms | 4.2 MB RAM**.

---

### 1.3 Temuan Query Berulang (N+1) pada Debugbar Tab Queries

Pada halaman terburuk (**Buku Rekap Nilai `/grades`**), inspeksi tab **Queries** pada Laravel Debugbar memperlihatkan badai kueri berulang (*repetitive N+1 queries*):

```sql
SELECT * FROM `submissions` 
WHERE `submissions`.`assignment_id` = 1 
  AND `submissions`.`user_id` = 4 
LIMIT 1;

SELECT * FROM `grades` 
WHERE `grades`.`submission_id` = 12 
LIMIT 1;

SELECT * FROM `submissions` 
WHERE `submissions`.`assignment_id` = 1 
  AND `submissions`.`user_id` = 5 
LIMIT 1;

SELECT * FROM `grades` 
WHERE `grades`.`submission_id` = 13 
LIMIT 1;
```

* **Akar Masalah Teknis:**  
  Di dalam template view `resources/views/grades/index.blade.php`, perulangan luar melakukan iterasi pada 30 mahasiswa, dan perulangan dalam melakukan iterasi pada setiap tugas ($30 \times 3 = 90$ iterasi). Tanpa pemuatan relasi bertingkat yang rapi di `GradeController`, pemanggilan `$assignment->submissions` dan `$sub->grade` memicu lazy loading individual ke MySQL.

---

### 1.4 Hasil Analisis `EXPLAIN` Database pada Kueri Lambat

Kueri pencarian submission mahasiswa yang sering berulang diuji menggunakan perintah `EXPLAIN` di terminal MySQL:

```sql
EXPLAIN SELECT * FROM `submissions` WHERE `user_id` = 15;
```

#### Hasil SEBELUM Indeks Ditambahkan:
* `table`: `submissions`
* **`type`**: **`ALL`** (*Full Table Scan* — MySQL memindai setiap baris tabel pada disk penyimpanan)
* `possible_keys`: `NULL`
* `key`: `NULL`
* **`rows`**: **`105`** (seluruh baris tabel diperiksa satu demi satu)
* `Extra`: `Using where`

#### Hasil SESUDAH Indeks Ditambahkan (`idx_submissions_user_id`):
* `table`: `submissions`
* **`type`**: **`ref`** (Pencarian cepat berbasis B-Tree index)
* `possible_keys`: `idx_submissions_user_id`
* `key`: `idx_submissions_user_id`
* **`rows`**: **`3`** (hanya 3 baris milik mahasiswa bersangkutan yang dibaca)
* `Extra`: `Using index condition`

---

## 2. Tahap 2: BREAK — Tabel Eksperimen 7 Kerusakan Performa (45 Menit)

Berikut adalah catatan pelaksanaan 7 eksperimen pengujian kerusakan performa sesuai panduan Modul 11.3:

| # | Percobaan Kerusakan (*Action*) | Prediksi Sebelum Mencoba | Hasil Pengamatan Aktual | Bahaya / Pelajaran Utama & Mitigasi |
|:---:|---|---|---|---|
| **1** | Pasang `Model::preventLazyLoading()`, jalankan seluruh halaman aplikasi | Halaman yang mengakses relasi tanpa `with()` akan melempar exception error. | Muncul `LazyLoadingViolationException` pada halaman yang memanggil relasi secara implisit di Blade. | **Deteksi Dini:** Sangat ampuh mencegah N+1 lolos ke produksi. Wajib diaktifkan di `AppServiceProvider::boot()` untuk non-produksi. |
| **2** | Hapus klausa `with('lecturer')` dari `CourseController@index`, ukur ulang | Jumlah kueri akan melonjak drastis sebanding dengan jumlah baris data yang tampil. | Kueri naik dari 5 kueri menjadi 20 kueri untuk 15 data MK. Waktu respons bertambah ~45 ms. | Masalah N+1 klasik. Solusi: Gunakan eager loading `with(...)`. |
| **3** | Ganti `$course->withCount('students')` menjadi `count($course->students)`, ukur memori | Memori RAM server PHP membengkak karena ribuan objek model User dimuat ke RAM. | Konsumsi memori melonjak dari 4.5 MB menjadi 14.8 MB. Database mengirim seluruh teks data pengguna ke PHP. | `count()` mengevaluasi koleksi objek di memori PHP, sedangkan `withCount()` mengeksekusi `COUNT(*)` langsung di mesin database MySQL tanpa membebani RAM PHP. |
| **4** | Hapus indeks dari kolom `submissions.user_id`, jalankan perintah `EXPLAIN` | MySQL beralih dari pencarian indeks (`ref`/`range`) menjadi pemindaian tabel penuh (`ALL`). | Kolom `type` berubah menjadi `ALL`, dan kolom `rows` melonjak memindai seluruh record (105 baris). | Menguras I/O disk dan CPU server. Solusi: Pasang indeks B-Tree tertarget pada kolom yang sering muncul di klausa `WHERE`. |
| **5** | Naikkan seeder menjadi 300 mahasiswa dan 1.000 submission, buka rekap nilai | Waktu respons halaman yang belum teroptimasi akan membengkak hingga detik (*lagging*). | Waktu respons rekap nilai membengkak hingga >2,8 detik dengan ratusan kueri SQL. | Skala data kecil menutupi masalah performa. Pengujian wajib memakai volume data yang realistis. |
| **6** | Jalankan `php artisan config:cache`, lalu panggil `env('APP_NAME')` dari controller | Fungsi `env()` akan menghasilkan nilai `null`. | Layar menampilkan teks kosong / `null` karena file `.env` tidak lagi dibaca setelah cache konfigurasi aktif. | **Penyebab Crash Paling Umum di Produksi:** Fungsi `env()` hanya boleh dipanggil di dalam `config/*.php`. Di controller wajib gunakan `config('app.name')`. |
| **7** | Pasang cache 10 menit pada nilai mahasiswa, lalu ubah nilai via dosen | Mahasiswa akan melihat nilai lama (*data basi*) selama 10 menit meski dosen sudah menilai. | Mahasiswa memuat ulang halaman namun nilai baru tidak muncul hingga TTL cache 10 menit habis. | **Pelanggaran Integritas Akademik:** Nilai mahasiswa DILARANG di-cache statis karena membutuhkan konsistensi instan (*strong consistency*). |

---

## 3. Tahap 3: FIX — Analisis & Perbaikan Repo Cacat (Branch `w11`)

Pada latihan branch `w11` di repositori `kampuslms-broken`, diidentifikasi dan diperbaiki **6 masalah performa kritis**:

1. **N+1 Query Bertingkat pada Halaman Rekap Nilai:**
   * *Masalah:* Relasi Course → Assignments → Submissions → Grade dipanggil berulang di dalam nested loop Blade.
   * *Perbaikan:* Menerapkan eager loading bertingkat di controller: `Course::with(['assignments.submissions.grade', 'assignments.submissions.student:id,name,nim_nip', 'students:id,name,nim_nip'])`.
2. **Penggunaan `count()` Boros Memori:**
   * *Masalah:* Memanggil `count($course->students)` yang memuat seluruh baris model ke RAM server.
   * *Perbaikan:* Mengganti menjadi `withCount('students')` dan mengakses `$course->students_count`.
3. **Pengambilan Seluruh Kolom (`SELECT *`):**
   * *Masalah:* Kueri mahasiswa memuat seluruh kolom teks termasuk kolom yang tidak dipakai di tabel nilai.
   * *Perbaikan:* Membatasi proyeksi kolom spesifik: `select('users.id', 'users.name', 'users.nim_nip')`.
4. **Ketiadaan Indeks Database pada Kolom Filter Kunci:**
   * *Masalah:* Kueri filtering submission dan status assignment melakukan *Full Table Scan* (`ALL`).
   * *Perbaikan:* Menambahkan indeks komposit dan indeks filter melalui file migrasi performa.
5. **Pemuatan Seluruh Data Tanpa Batas:**
   * *Masalah:* Menampilkan seluruh data tanpa batasan atau eager load scoped.
   * *Perbaikan:* Menerapkan filter kelas (`course_id`) dan pagination terkontrol.
6. **Pemanggilan `env()` Liar di Luar `config/`:**
   * *Masalah:* Controller memanggil fungsi `env('...')` langsung, yang akan rusak (`null`) saat `config:cache` dijalankan.
   * *Perbaikan:* Mengganti seluruh fungsi `env()` menjadi `config('...')`.

### ⏱️ Bukti Pengukuran Sebelum vs Sesudah (Perbaikan Repo Cacat):
* **Sebelum Perbaikan:** 87 Query | 1.380 ms | 21.4 MB
* **Sesudah Perbaikan:** 6 Query | 148 ms | 6.2 MB

---

## 4. Tahap 4: BUILD — Kontribusi Nyata Shabrina Putri Az-Zahra

Sebagai pemegang peran **Database & Testing / Backend Logic**, implementasi optimasi performa saya di repositori KampusLMS Kelompok 06 berfokus pada:

### 1. Optimasi Berat Kueri Rekap Nilai (`app/Http/Controllers/GradeController.php`)
* Mengganti kueri awal dengan **Eager Loading Bertingkat** dan **Pembatasan Proyeksi Kolom (Column Selection)**:
  ```php
  $relations = [
      'assignments' => fn (HasMany $query) => $query->orderBy('due_at'),
      'assignments.submissions' => function (HasMany $query) use ($user): void {
          $query->with([
              'student:id,name,nim_nip',
              'grade',
          ]);

          if ($user->role === 'mahasiswa') {
              $query->where('user_id', $user->id);
          }
      },
  ];

  if ($user->role !== 'mahasiswa') {
      // Batasi kolom hanya id, name, nim_nip untuk mencegah pemborosan alokasi memori RAM
      $relations['students'] = fn (BelongsToMany $query) => $query->select('users.id', 'users.name', 'users.nim_nip')->orderBy('name');
  }
  ```
* **Hasil Pengukuran:**
  * Jumlah kueri SQL turun drastis dari **87 query menjadi 6 query** (target modul < 15 query tercapai 100%).
  * Waktu eksekusi turun dari **1.380 ms menjadi 148 ms** (peningkatan kecepatan hampir 10x lipat).
  * Penggunaan memori RAM PHP turun dari **21.4 MB menjadi 6.2 MB**.

### 2. Refaktor Agregasi Statistik Dashboard (`app/Http/Controllers/DashboardController.php`)
* **Dashboard Dosen:** Mengganti `$myCourses->sum(fn($c) => $c->students->count())` menjadi `Course::withCount('students')` dan menjumlahkan `$myCourses->sum('students_count')`. Mencegah pemuatan ratusan objek User ke RAM server.
* **Dashboard Mahasiswa:** Mengganti perulangan kueri terpisah di dalam `foreach ($myCourses as $c) { $c->assignments()->where('status', 'active')->count() }` dengan pemuatan batch menggunakan:
  ```php
  $activeUser->courses()
      ->with('lecturer')
      ->withCount(['assignments as active_assignments_count' => fn ($q) => $q->where('status', 'active')])
      ->get();
  ```
* **Optimasi Template Blade (`dashboard.blade.php`):** Menyesuaikan template Blade agar memanfaatkan atribut agregasi `students_count` dan `active_assignments_count` tanpa memicu lazy loading violation.

### 3. Implementasi Caching Statistik Dashboard & Cache Invalidation
* **Penerapan Caching 10 Menit (`Cache::remember`):**
  Menerapkan caching untuk ringkasan agregasi angka dashboard pengguna:
  ```php
  $stats = Cache::remember("dashboard.stats.user.{$activeUser->id}", now()->addMinutes(10), function () use ($courses) {
      return [ ... ];
  });
  ```
* **Pembersihan Cache Otomatis (*Event-Driven Cache Invalidation*):**
  Memasang lifecycle hooks `booted()` pada model [`app/Models/Assignment.php`](../../app/Models/Assignment.php) dan [`app/Models/Submission.php`](../../app/Models/Submission.php):
  ```php
  // Pada Assignment.php:
  protected static function booted(): void
  {
      static::saved(function (Assignment $assignment) {
          Cache::forget("dashboard.stats.user.{$assignment->created_by}");
          if ($assignment->course_id) {
              Cache::forget("course.{$assignment->course_id}.stats");
          }
      });

      static::deleted(function (Assignment $assignment) {
          Cache::forget("dashboard.stats.user.{$assignment->created_by}");
          if ($assignment->course_id) {
              Cache::forget("course.{$assignment->course_id}.stats");
          }
      });
  }
  ```
* **Pemberlakuan Kebijakan Integritas Akademik (Anti-Stale Data):**
  Data nilai mahasiswa (`grades`) dan status berkas tugas (`submissions`) **DILARANG KERAS di-cache statis**. Data nilai tetap diambil secara realtime dan terproteksi Policy saat diakses.

---

## 5. Tahap 5: CHECKPOINT MINGGU 11 (Persiapan Sesi Wawancara)

Berikut adalah jawaban lengkap dan argumentasi teknis untuk 6 butir evaluasi Checkpoint Modul 11.4:

### 1. Buka `docs/performa.md`. Halaman mana yang terburuk sebelum optimisasi? Kenapa halaman itu?
* **Jawaban:** Halaman **Buku Rekap Nilai (`/grades`)**, dengan catatan metrik terburuk: **87 kueri, waktu respons 1.380 ms, dan penggunaan memori 21.4 MB**.
* **Alasan Teknis:** Halaman ini menyajikan matriks dua dimensi yang merelasikan entitas bertingkat: *Mata Kuliah → Daftar Seluruh Tugas → Daftar Seluruh Mahasiswa Peserta → Submission Jawaban → Grade Nilai*. Tanpa eager loading bertingkat, perulangan tabel Blade memicu kueri individual ke MySQL untuk setiap sel mahasiswa $\times$ tugas ($30 \times 3 = 90$ kueri berulang).

### 2. Peragakan N+1 di aplikasi Anda: matikan `with()` pada satu halaman dan tunjukkan lonjakan query di Debugbar.
* **Peragaan:**
  1. Pada `CourseController@index`, hapus klausa `->with('lecturer')` sehingga hanya memanggil `Course::paginate(15)`.
  2. Muat ulang halaman `/courses` di peramban dan perhatikan panel Laravel Debugbar.
  3. Jumlah kueri seketika melonjak dari **5 kueri menjadi 20 kueri** (1 kueri untuk mengambil 15 baris mata kuliah, ditambah 15 kueri `SELECT * FROM users WHERE id = ?` untuk mengambil dosen pada setiap baris).
  4. Jika `Model::preventLazyLoading(! app()->isProduction())` aktif, Laravel langsung melempar error `LazyLoadingViolationException`, membuktikan deteksi otomatis bekerja sempurna.

### 3. Apa beda `withCount('students')` dan `count($course->students)`? Kenapa yang kedua boros?
* **Perbedaan Mendasar:**
  - **`$course->withCount('students')`:** Dijalankan langsung di mesin basis data MySQL menggunakan subquery agregasi `SELECT COUNT(*) FROM course_user WHERE ...`. Yang dikirim ke server web hanyalah satu angka integer kecil (`students_count`). Sangat hemat memori RAM dan bandwidth I/O.
  - **`count($course->students)`:** Menjalankan kueri `SELECT * FROM users INNER JOIN course_user ...` yang mengambil seluruh kolom dari puluhan/ratusan mahasiswa, lalu menginstansiasinya menjadi objek model Eloquent User di RAM PHP, baru kemudian dihitung menggunakan fungsi `count()` bawaan PHP.
* **Mengapa Boros:** Karena memboroskan alokasi memori RAM PHP dan membebani transfer data jaringan basis data hanya untuk mengetahui sebuah angka total.

### 4. Tunjukkan index yang Anda tambahkan. Jalankan `EXPLAIN` dan buktikan ia terpakai.
* **Indeks yang Ditambahkan:** Indeks pada kolom `submissions(user_id)` (`idx_submissions_user_id`) pada file migrasi `2026_10_30_130412_add_performance_indexes_to_tables.php`.
* **Pembuktian `EXPLAIN`:**
  ```sql
  EXPLAIN SELECT * FROM submissions WHERE user_id = 15;
  ```
  * Sebelum indeks: Kolom `type = ALL` (*Full Table Scan*), `key = NULL`, `rows = 105`.
  * Sesudah indeks: Kolom `type = ref` (*B-Tree Index Lookup*), `key = idx_submissions_user_id`, `rows = 3`.
  * MySQL langsung melompat ke *leaf node* indeks tanpa membaca seluruh isi tabel.

### 5. Sebutkan satu data di aplikasi Anda yang TIDAK BOLEH di-cache. Kenapa?
* **Jawaban:** **Nilai Mahasiswa (`grades`) dan Status Pengumpulan Tugas (`submissions`)**.
* **Alasan Akademik & Keamanan:**
  Data nilai menuntut konsistensi seketika (*strong consistency*). Jika nilai di-cache selama 10 menit, mahasiswa yang baru saja dinilai oleh dosen akan melihat nilai kosong atau nilai lama, menimbulkan kebingungan dan keluhan akademik (*stale data complaint*). Selain itu, status pengumpulan tugas (*Tepat Waktu / Terlambat*) harus instan agar tidak terjadi sengketa batas waktu (*deadline*).

### 6. Apa yang terjadi pada `env()` setelah `config:cache`? Kenapa ini penting minggu depan?
* **Jawaban:** Setelah perintah `php artisan config:cache` dieksekusi, seluruh pemanggilan fungsi `env('KEY')` di luar folder `config/*.php` akan mengembalikan nilai **`null`**.
* **Alasan Teknis:** Demi kecepatan eksekusi di server produksi, Laravel menggabungkan seluruh file konfigurasi ke dalam satu file cache PHP serial (`bootstrap/cache/config.php`) dan **berhenti membaca file `.env`**.
* **Urgensi untuk Minggu 12 (Deployment):** Di server produksi nanti, `config:cache` wajib dijalankan. Jika di controller atau view masih ada pemanggilan `env(...)`, aplikasi akan mengalami *crash* error 500 diam-diam karena variabel konfigurasi bernilai `null`.

