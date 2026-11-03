# DOKUMENTASI MASTER OPTIMISASI PERFORMA & ANALISIS EFISIENSI
## Proyek: KampusLMS (EduKampus) — Laravel 12
**Mata Kuliah:** SI2514024 Pemrograman Web  
**Modul:** Minggu 11 — Optimisasi Performa  
**Kelompok:** Kelompok 06  
**Kontributor:**
* Tika Mila Wahyuni (10241070) — *Deteksi N+1 Global, Migrasi Indeks Database, Profiling Baseline*
* Shabrina Putri Azzahra (10241066) — *Optimasi Eager Load Rekap Nilai (< 15 query), withCount, Caching Dashboard*
* Raihandy Wijaya (10241064) — *Optimasi REST API v1, Sanitasi env() Liar, Uji config:cache*
* Oktavia Nur Rahmadani (10241060) — *Profiling Debugbar Akhir, Komparasi Sebelum-Sesudah, Audit Frontend Blade*

---

## 1. Tahap READ — Pengukuran Baseline Aplikasi (Sebelum Optimasi)

Pengukuran dilakukan pada basis data dengan volume data realistis dari seeder penuh (`php artisan migrate:fresh --seed`) yang memuat **30 mahasiswa, 5 mata kuliah, 15 tugas perkuliahan, 105 pengumpulan tugas (submissions), dan 60 catatan nilai (grades)**. Alat ukur yang digunakan adalah **Laravel Debugbar** dan **Laravel Telescope** di lingkungan pengembangan lokal (*PHP 8.5 / MySQL 8.0*).

### 1.1 Tabel Pengukuran Baseline 7 Halaman Utama

Berikut adalah hasil pengukuran dasar (*baseline*) sebelum penerapan optimasi eager loading, indeks B-Tree, dan caching:

| Halaman | URL / Rute | Jumlah Query | Waktu Respons (ms) | Penggunaan Memori (MB) |
|---|---|:---:|:---:|:---:|
| **Daftar mata kuliah** | `/courses` | 5 query | 42 ms | 4.5 MB |
| **Detail mata kuliah** | `/courses/4` | 7 query | 68 ms | 5.1 MB |
| **Daftar tugas** | `/assignments` | 4 query | 35 ms | 4.2 MB |
| **Daftar pengumpulan (dosen)** | `/submissions` | 9 query | 88 ms | 5.6 MB |
| **Rekap nilai** | `/grades` | **87 query** | **1.380 ms** | **21.4 MB** |
| **Dashboard mahasiswa** | `/dashboard` (mhs) | 16 query | 210 ms | 6.5 MB |
| **Dashboard dosen** | `/dashboard` (dosen) | 24 query | 340 ms | 8.2 MB |

---

## 2. Analisis & Pengurutan Halaman dari yang Terburuk

Berdasarkan data pengukuran awal di atas, urutan halaman dari yang memiliki performa paling buruk ke paling efisien adalah sebagai berikut:

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

## 7. Tahap BUILD — Pengukuran Aplikasi SESUDAH Optimasi Penuh

Setelah seluruh perbaikan eager loading (`with`, `withCount`), indeks database B-Tree, caching ringkasan dashboard, dan sanitasi `config()` selesai diintegrasikan, dilakukan pengukuran ulang menyeluruh dengan Laravel Debugbar:

| Halaman Utama | URL / Rute | Jumlah Query (Sesudah) | Waktu Respons (Sesudah) | Memori RAM (Sesudah) |
|---|---|:---:|:---:|:---:|
| **Daftar mata kuliah** | `/courses` | 5 query | 38 ms | 4.3 MB |
| **Detail mata kuliah** | `/courses/4` | 6 query | 52 ms | 4.8 MB |
| **Daftar tugas** | `/assignments` | 4 query | 30 ms | 4.0 MB |
| **Daftar pengumpulan (dosen)** | `/submissions` | 6 query | 64 ms | 5.0 MB |
| **Rekap nilai** | `/grades` | **6 query** | **148 ms** | **6.2 MB** |
| **Dashboard mahasiswa** | `/dashboard` (mhs) | 4 query | 45 ms | 4.8 MB |
| **Dashboard dosen** | `/dashboard` (dosen) | 4 query | 55 ms | 5.2 MB |

---

## 8. Tabel Komparasi Head-to-Head (Sebelum vs Sesudah Optimasi)

Berikut adalah tabel perbandingan sebelum dan sesudah optimasi yang membuktikan efisiensi sistem:

| Halaman Utama | Query Sebelum | Query Sesudah | Selisih Query | Waktu Sebelum | Waktu Sesudah | Peningkatan Kecepatan | Memori Sebelum | Memori Sesudah |
|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| **Rekap nilai** | **87** | **6** | **-81 (-93.1%)** | **1.380 ms** | **148 ms** | **9.3x Lebih Cepat** | 21.4 MB | 6.2 MB (-71.0%) |
| **Dashboard dosen** | **24** | **4** | **-20 (-83.3%)** | **340 ms** | **55 ms** | **6.2x Lebih Cepat** | 8.2 MB | 5.2 MB (-36.6%) |
| **Dashboard mahasiswa** | **16** | **4** | **-12 (-75.0%)** | **210 ms** | **45 ms** | **4.7x Lebih Cepat** | 6.5 MB | 4.8 MB (-26.2%) |
| **Daftar pengumpulan** | **9** | **6** | **-3 (-33.3%)** | **88 ms** | **64 ms** | **1.4x Lebih Cepat** | 5.6 MB | 5.0 MB (-10.7%) |
| **Detail mata kuliah** | **7** | **6** | **-1 (-14.3%)** | **68 ms** | **52 ms** | **1.3x Lebih Cepat** | 5.1 MB | 4.8 MB (-5.9%) |
| **Daftar mata kuliah** | **5** | **5** | **0 (Terkontrol)** | **42 ms** | **38 ms** | **1.1x Lebih Cepat** | 4.5 MB | 4.3 MB (-4.4%) |
| **Daftar tugas** | **4** | **4** | **0 (Terkontrol)** | **35 ms** | **30 ms** | **1.2x Lebih Cepat** | 4.2 MB | 4.0 MB (-4.8%) |

### Analisis Efisiensi Kunci:
1. **Penurunan Kueri Rekap Nilai (-93.1%):** Kueri berkurang dari 87 kueri menjadi hanya 6 kueri. Target modul (< 15 query) tercapai dengan sangat memuaskan.
2. **Pemangkasan Waktu Eksekusi (-89.3%):** Waktu loading halaman rekap nilai terpangkas dari 1,38 detik menjadi hanya 0,14 detik (responsif instan).
3. **Penghematan Memori RAM (-71.0%):** Memori berkurang drastis dari 21.4 MB menjadi 6.2 MB karena relasi tidak memuat seluruh objek User secara redundan, melainkan hanya memproyeksikan kolom esensial (`select('id', 'name', 'nim_nip')`).

---

## 9. Audit Frontend & Template Blade

Sesuai arahan Modul 11.1 (Frontend Optimization):
1. **Atribut `loading="lazy"`:** Seluruh rendering media bitmap disiapkan dengan atribut `loading="lazy"` guna menunda pemuatan gambar di luar viewport:
   ```html
   <img src="..." alt="..." loading="lazy">
   ```
2. **Pemanfaatan Vector Icons & Lightweight Avatars:** Aplikasi EduKampus mengadopsi font vektor **Google Material Symbols** dan avatar CSS inisial nama (`{{ strtoupper(substr($user->name, 0, 1)) }}`), sehingga meminimalkan HTTP request aset gambar eksternal.
3. **Pembersihan Logika Kueri dari Blade:** Tidak ada satupun pemanggilan query database langsung (misal: `Model::all()`) di dalam file template Blade. Seluruh data dipasok dari controller.
4. **Stateful Pagination:** Seluruh pagination mempertahankan filter pencarian via `{{ $items->withQueryString()->links() }}`.

---

## 10. Checklist Pemenuhan Target BUILD Minggu 11

| Butir Spesifikasi Modul BUILD 11.3 | Target | Realisasi Kelompok 06 | Status |
|---|---|---|:---:|
| **1. Tabel Sebelum & Sesudah** | Tersedia di `docs/performa.md` | Lengkap untuk 7 halaman utama | ✅ Tuntas |
| **2. `Model::preventLazyLoading()`** | Aktif di non-produksi | Terpasang di `AppServiceProvider` & bebas error | ✅ Tuntas |
| **3. Perbaikan Query N+1** | Seluruh N+1 teratasi | Eager load bertingkat di seluruh controller & API | ✅ Tuntas |
| **4. Migrasi Indeks Tambahan** | Indeks terpasang + komentar kueri | File migrasi indeks B-Tree aktif di database | ✅ Tuntas |
| **5. Pagination Halaman Daftar** | Tidak memuat seluruh tabel | Seluruh daftar menggunakan `paginate(15)` | ✅ Tuntas |
| **6. Caching Dashboard** | Minimal 1 bagian di-cache + invalidasi | Ringkasan stats di-cache 10 menit + auto-invalidation | ✅ Tuntas |
| **7. Sanitasi `env()` Liar** | Nol pemanggilan `env()` di luar `config/` | 100% menggunakan `config('...')` | ✅ Tuntas |
| **8. Target Kueri Rekap Nilai** | **< 15 query** | **Tercapai 6 query** (Target < 15) | ✅ Tuntas |
