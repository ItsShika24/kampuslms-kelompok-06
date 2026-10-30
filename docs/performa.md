# DOKUMENTASI OPTIMISASI PERFORMA & ANALISIS EFISIENSI
## Proyek: KampusLMS (EduKampus) — Laravel 12
**Mata Kuliah:** SI2514024 Pemrograman Web  
**Modul:** Minggu 11 — Optimisasi Performa  
**Kelompok:** Kelompok 06  
**Penanggung Jawab Audit Awal (READ):** Tika Mila Wahyuni (NIM: 10241070)  

---

## 1. Tahap READ — Pengukuran Baseline Aplikasi (Sebelum Optimasi)

Pengukuran dilakukan pada basis data dengan volume data realistis dari seeder penuh (`php artisan migrate:fresh --seed`) yang memuat **30 mahasiswa, 5 mata kuliah, 15 tugas perkuliahan, 105 pengumpulan tugas (submissions), dan 60 catatan nilai (grades)**. Alat ukur yang digunakan adalah **Laravel Debugbar** dan **Laravel Telescope** di lingkungan pengembangan lokal (*PHP 8.3 / MySQL 8.0*).

### 1.1 Tabel Pengukuran 7 Halaman Utama

Berikut adalah hasil pengukuran dasar (*baseline*) sebelum penerapan optimasi eager loading, indeks B-Tree, dan caching:

| Halaman | Jumlah Query | Waktu Respons (ms) | Penggunaan Memori (MB) |
|---|:---:|:---:|:---:|
| **Daftar mata kuliah** (`/courses`) | 5 query | 42 ms | 4.5 MB |
| **Detail mata kuliah** (`/courses/{id}`) | 7 query | 68 ms | 5.1 MB |
| **Daftar tugas** (`/assignments`) | 4 query | 35 ms | 4.2 MB |
| **Daftar pengumpulan (dosen)** (`/submissions`) | 9 query | 88 ms | 5.6 MB |
| **Rekap nilai** (`/grades`) | **87 query** | **1.380 ms** | **21.4 MB** |
| **Dashboard mahasiswa** (`/dashboard`) | 16 query | 210 ms | 6.5 MB |
| **Dashboard dosen** (`/dashboard`) | 24 query | 340 ms | 8.2 MB |

---

## 2. Analisis & Pengurutan Halaman dari yang Terburuk

Berdasarkan data pengukuran di atas, urutan halaman dari yang memiliki performa paling buruk ke paling efisien adalah sebagai berikut:

```
[TERBURUK]                                                                          [TERCEPAT]
Rekap Nilai > Dashboard Dosen > Dashboard Mhs > Daftar Pengumpulan > Detail MK > Daftar MK > Daftar Tugas
 (1.380 ms)       (340 ms)        (210 ms)          (88 ms)        (68 ms)     (42 ms)     (35 ms)
```

1. **Peringkat 1 (Paling Buruk): Halaman Rekap Nilai (`/grades`)**  
   * **Metrik:** 87 query | 1.380 ms (1,38 detik) | 21.4 MB RAM.
   * **Penyebab:** Terjadi masalah *N+1 query storm* bertingkat di dalam loop matriks nilai mahasiswa $\times$ tugas. Halaman ini memang sengaja dirancang rapuh pada skema awal untuk menguji kemampuan profiling mahasiswa.
2. **Peringkat 2: Dashboard Dosen (`/dashboard`)**  
   * **Metrik:** 24 query | 340 ms | 8.2 MB RAM.
   * **Penyebab:** Menghitung total mahasiswa dengan memuat seluruh koleksi model User ke memori (`$myCourses->sum(fn($c) => $c->students->count())`) serta kueri submission berganda.
3. **Peringkat 3: Dashboard Mahasiswa (`/dashboard`)**  
   * **Metrik:** 16 query | 210 ms | 6.5 MB RAM.
   * **Penyebab:** Terdapat perulangan `foreach ($myCourses as $c)` yang menjalankan kueri agregasi terpisah `$c->assignments()->where('status', 'active')->count()` pada setiap baris mata kuliah yang diikuti.
4. **Peringkat 4: Daftar Pengumpulan / Submission Center Dosen (`/submissions`)**  
   * **Metrik:** 9 query | 88 ms | 5.6 MB RAM.
   * **Penyebab:** Memuat riwayat pengumpulan tugas beserta relasi ganda (student, assignment, course, grade) tanpa indeks gabungan status.
5. **Peringkat 5: Detail Mata Kuliah (`/courses/{id}`)**  
   * **Metrik:** 7 query | 68 ms | 5.1 MB RAM.
   * **Penyebab:** Memuat seluruh tab materi, tugas, mahasiswa terdaftar, dan mahasiswa yang belum terdaftar secara simultan.
6. **Peringkat 6: Daftar Mata Kuliah (`/courses`)**  
   * **Metrik:** 5 query | 42 ms | 4.5 MB RAM.
   * **Penyebab:** Menggunakan pagination 15 data dan eager loading `with('lecturer')` sehingga kueri relatif terkontrol.
7. **Peringkat 7 (Paling Ringan): Daftar Tugas Perkuliahan (`/assignments`)**  
   * **Metrik:** 4 query | 35 ms | 4.2 MB RAM.
   * **Penyebab:** Struktur kueri sederhana dan terfilter langsung berdasarkan `course_id`.

---

## 3. Investigasi Debugbar Tab Queries pada Halaman Terburuk (Rekap Nilai)

Saat membuka tab **Queries** pada Laravel Debugbar untuk halaman `/grades`, ditemukan pola kueri berulang (*repetitive queries*) yang dieksekusi terus-menerus:

### Pola Kueri N+1 yang Ditemukan:
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

### Akar Masalah:
* Pada template Blade matriks rekap nilai, perulangan luar melakukan iterasi terhadap seluruh mahasiswa terdaftar (`@foreach ($students as $student)`), dan perulangan dalam melakukan iterasi terhadap seluruh tugas (`@foreach ($assignments as $assignment)`).
* Untuk kelas dengan 30 mahasiswa dan 3 tugas, terjadi $30 \times 3 = 90$ pemeriksaan data. Karena relasi submission dan grade tidak dipetakan dalam hash map / koleksi terstruktur sebelum dikirim ke view, Eloquent terpaksa melakukan kueri baru ke MySQL untuk setiap sel tabel.

---

## 4. Analisis `EXPLAIN` Database pada Kueri Terlambat

Untuk kueri pencarian submission yang paling lambat dan sering berulang, dilakukan eksekusi perintah `EXPLAIN` langsung di console basis data MySQL:

```sql
EXPLAIN SELECT * FROM `submissions` WHERE `user_id` = 15;
```

### 4.1 Hasil `EXPLAIN` SEBELUM Optimasi (Tanpa Indeks):
| id | select_type | table | partitions | type | possible_keys | key | key_len | ref | rows | filtered | Extra |
|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---|
| 1 | SIMPLE | submissions | NULL | **ALL** | NULL | **NULL** | NULL | NULL | **105** | 10.00 | **Using where** |

* **Analisis Kritis:** Kolom `type = ALL` menandakan terjadinya **Full Table Scan**. MySQL harus membaca seluruh 105 record di disk untuk menyaring data milik mahasiswa nomor 15 karena belum ada indeks B-Tree pada kolom `user_id`.

---

### 4.2 Hasil `EXPLAIN` SESUDAH Penambahan Indeks (`idx_submissions_user_id`):
Setelah migrasi indeks performa [`2026_10_30_130412_add_performance_indexes_to_tables.php`](../database/migrations/2026_10_30_130412_add_performance_indexes_to_tables.php) dijalankan:

| id | select_type | table | partitions | type | possible_keys | key | key_len | ref | rows | filtered | Extra |
|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---|
| 1 | SIMPLE | submissions | NULL | **ref** | idx_submissions_user_id | **idx_submissions_user_id** | 8 | const | **3** | 100.00 | **Using index condition** |

* **Hasil:** Kolom `type` berubah drastis menjadi **`ref`**, dan estimasi pemindaian baris (`rows`) anjlok dari **105 baris menjadi 3 baris**. Basis data langsung melompat ke posisi leaf node indeks B-Tree tanpa memindai seluruh tabel.

---

## 5. Indeks Database yang Diterapkan & Trade-Off

Daftar indeks performa yang ditambahkan pada migrasi:

1. `submissions(user_id)` (`idx_submissions_user_id`)  
   * **Kueri yang Diuntungkan:** Filter riwayat pengumpulan tugas oleh mahasiswa dan verifikasi status kumpul.
   * **Biaya Penulisan:** Tambahan overhead I/O saat mahasiswa mengunggah submission baru.
2. `assignments(status)` & `assignments(course_id, status)` (`idx_assignments_course_status`)  
   * **Kueri yang Diuntungkan:** Perhitungan tugas aktif di dashboard mahasiswa dan penyaringan tugas berstatus non-draft.
   * **Biaya Penulisan:** Sedikit memperlambat pembuatan tugas baru oleh dosen.
3. `notifications(notifiable_type, notifiable_id, read_at)` (`idx_notifications_unread_polling`)  
   * **Kueri yang Diuntungkan:** Polling notifikasi berkala di navbar (`read_at IS NULL`). Menghasilkan *Covering Index* sehingga database tidak perlu membaca data tabel asli.
   * **Biaya Penulisan:** Overhead saat listener membuat baris notifikasi baru atau menandai notifikasi telah dibaca.

---

## 6. Strategi Caching: Apa yang Boleh dan TIDAK BOLEH Di-Cache

Penerapan cache sistem menggunakan kaidah integritas data akademik:

| Data Aplikasi | Kebijakan Cache | Alasan & Dampak Data Basi (*Stale Data*) |
|---|:---:|---|
| **Statistik Ringkasan Dashboard** (Total MK, Total SKS, Jumlah Mahasiswa Terdaftar) | **BOLEH** (TTL: 10 Menit) | Perubahan jumlah mata kuliah atau SKS tidak terjadi setiap detik. Cache dibersihkan otomatis saat model `Course` disimpan atau dihapus (*Event-driven cache invalidation*). |
| **Nilai Akademik Mahasiswa (`grades`)** | ❌ **HARAM DI-CACHE** | Nilai bersifat sensitif dan membutuhkan konsistensi instan (*strong consistency*). Jika di-cache statis 10 menit, mahasiswa akan panik melihat nilai lama setelah dosen mengumumkan pembaruan nilai. |
| **Status Pengumpulan Tugas (`submissions`)** | ❌ **HARAM DI-CACHE** | Status pengumpulan (*Tepat Waktu / Terlambat*) harus seketika berubah saat mahasiswa menekan tombol submit. Keterlambatan visual dapat memicu sengketa tenggat waktu (*deadline*). |
| **Notifikasi Belum Dibaca (*Unread Count*)** | ❌ **HARAM DI-CACHE** | Angka lonceng notifikasi di navbar harus merefleksikan event realtime/polling secara presisi. |

---

## 7. Rangkuman Target Akhir Optimisasi (Minggu 11)

| Metrik Kunci | Kondisi Awal (Baseline) | Target Setelah Optimasi (BUILD) | Status |
|---|:---:|:---:|:---:|
| **Kueri Rekap Nilai** | 87 query | **< 15 query** (Target Modul 11.3: < 15 query) | Tercapai via Eager Loading & Hash Map |
| **Waktu Rekap Nilai** | 1.380 ms | **< 200 ms** | Teroptimasi |
| **Deteksi N+1 Global** | Tidak Aktif (Bisa Lolos) | `Model::preventLazyLoading(true)` di AppServiceProvider | Aktif & Berjalan |
| **Indeks Database Kunci** | Bawaan Foreign Key | Indeks Komposit & Filter Tambahan Terpasang | Migrasi Sukses |
