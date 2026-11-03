# Laporan Praktikum Minggu 11 — Optimisasi Performa
## Nama: Oktavia Nur Rahmadani
## NIM: 10241060
## Kelompok: Kelompok 06 (EduKampus)
**Peran Utama:** Frontend, Profiling & Dokumentasi Performa  
**Repositori:** KampusLMS (Laravel 12)  
**Dokumen Master Performa:** [`docs/performa.md`](../performa.md)  

---

## 1. Tahap 1: READ — Pengukuran Baseline 7 Halaman Utama (40 Menit)

Sesuai instruksi Modul 11.1 dan 11.3, optimasi performa harus didasarkan pada data empiris terukur (*"jangan menebak"*). Sebelum pengukuran dilakukan, seeder penuh telah dijalankan:

```bash
php artisan migrate:fresh --seed
```

Dataset memuat volume data realistis: **30 mahasiswa terdaftar, 3 dosen pengampu, 1 admin, 5 mata kuliah, 15 tugas perkuliahan, 105 berkas submission pengumpulan tugas, dan 60 catatan nilai (grades)**. Pengukuran dilakukan menggunakan **Laravel Debugbar** dan **Laravel Telescope** di lingkungan lokal (*PHP 8.5 / MySQL 8.0*).

### 1.1 Tabel Pengukuran Baseline (Kondisi Awal SEBELUM Optimasi)

Berikut adalah tabel pengukuran pada seluruh 7 halaman utama aplikasi KampusLMS sebelum optimasi:

| Halaman | URL / Rute | Jumlah Query | Waktu Respons (ms) | Memori (MB) |
|---|---|:---:|:---:|:---:|
| **Daftar mata kuliah** | `/courses` | 5 query | 42 ms | 4.5 MB |
| **Detail mata kuliah** | `/courses/4` | 7 query | 68 ms | 5.1 MB |
| **Daftar tugas** | `/assignments` | 4 query | 35 ms | 4.2 MB |
| **Daftar pengumpulan (dosen)** | `/submissions` | 9 query | 88 ms | 5.6 MB |
| **Rekap nilai** | `/grades` | **87 query** | **1.380 ms** | **21.4 MB** |
| **Dashboard mahasiswa** | `/dashboard` (mhs) | 16 query | 210 ms | 6.5 MB |
| **Dashboard dosen** | `/dashboard` (dosen) | 24 query | 340 ms | 8.2 MB |

---

### 1.2 Urutan Halaman dari yang Terburuk

Berdasarkan kombinasi waktu eksekusi kueri, jumlah kueri, dan konsumsi memori RAM:

1. **Peringkat 1 (Paling Kritis/Terburuk): Rekap Nilai (`/grades`)**  
   * **87 query | 1.380 ms | 21.4 MB**.  
   * Halaman ini menjadi titik kemacetan (*bottleneck*) terparah akibat kueri berulang di dalam matriks tabel per mahasiswa per tugas.
2. **Peringkat 2: Dashboard Dosen (`/dashboard`)**  
   * **24 query | 340 ms | 8.2 MB**.  
   * Memuat seluruh koleksi model User mahasiswa ke RAM server PHP demi menghitung agregasi total mahasiswa bimbingan.
3. **Peringkat 3: Dashboard Mahasiswa (`/dashboard`)**  
   * **16 query | 210 ms | 6.5 MB**.  
   * Perulangan `foreach` kueri terpisah per mata kuliah untuk menghitung tugas aktif.
4. **Peringkat 4: Daftar Pengumpulan / Submission Dosen (`/submissions`)**  
   * **9 query | 88 ms | 5.6 MB**.
5. **Peringkat 5: Detail Mata Kuliah (`/courses/{id}`)**  
   * **7 query | 68 ms | 5.1 MB**.
6. **Peringkat 6: Daftar Mata Kuliah (`/courses`)**  
   * **5 query | 42 ms | 4.5 MB**.
7. **Peringkat 7 (Paling Cepat): Daftar Tugas (`/assignments`)**  
   * **4 query | 35 ms | 4.2 MB**.

---

### 1.3 Temuan Query Berulang (N+1) pada Debugbar Tab Queries

Pada halaman terburuk (**Buku Rekap Nilai `/grades`**), tab **Queries** pada Laravel Debugbar memperlihatkan badai kueri berulang (*repetitive N+1 queries*):

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

* **Penyebab:** Pada template Blade matriks rekap nilai, perulangan luar melakukan iterasi pada 30 mahasiswa terdaftar, dan perulangan dalam melakukan iterasi pada setiap tugas ($30 \times 3 = 90$ iterasi). Tanpa pemuatan relasi bertingkat, pemanggilan `$assignment->submissions` dan `$sub->grade` memicu lazy loading individual ke MySQL.

---

### 1.4 Hasil Analisis `EXPLAIN` Database pada Kueri Lambat

Kueri pencarian submission mahasiswa yang sering berulang diuji menggunakan perintah `EXPLAIN` di terminal basis data MySQL:

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

Berikut adalah hasil pengamatan aktual dari 7 percobaan pengujian kerusakan performa sesuai panduan Modul 11.3:

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

## 3. Tahap 3: BUILD — Kontribusi Nyata Oktavia Nur Rahmadani

Sebagai pemegang peran **Frontend, Profiling & Dokumentasi Performa**, kontribusi utama saya meliputi:

### 1. Pengukuran Akhir Menggunakan Laravel Debugbar (Sesudah Optimasi Penuh)
Setelah seluruh perbaikan kode oleh Tika (indeks database & preventLazyLoading), Shabrina (eager load rekap nilai & cache dashboard), dan Raihandy (REST API & sanitasi env) diintegrasikan ke cabang utama, saya melakukan pengukuran ulang pada seluruh 7 halaman utama:

| Halaman Utama | Jumlah Query (Sesudah) | Waktu Respons (Sesudah) | Memori RAM (Sesudah) |
|---|:---:|:---:|:---:|
| **Daftar mata kuliah** (`/courses`) | 5 query | 38 ms | 4.3 MB |
| **Detail mata kuliah** (`/courses/4`) | 6 query | 52 ms | 4.8 MB |
| **Daftar tugas** (`/assignments`) | 4 query | 30 ms | 4.0 MB |
| **Daftar pengumpulan (dosen)** (`/submissions`) | 6 query | 64 ms | 5.0 MB |
| **Rekap nilai** (`/grades`) | **6 query** | **148 ms** | **6.2 MB** |
| **Dashboard mahasiswa** (`/dashboard`) | 4 query | 45 ms | 4.8 MB |
| **Dashboard dosen** (`/dashboard`) | 4 query | 55 ms | 5.2 MB |

---

### 2. Tabel Komparasi Head-to-Head (Sebelum vs Sesudah Optimasi)

Berikut adalah pembuktian keberhasilan optimasi tim Kelompok 06:

| Halaman Utama | Query Sebelum | Query Sesudah | Selisih Query | Waktu Sebelum | Waktu Sesudah | Peningkatan Kecepatan | Memori Sebelum | Memori Sesudah |
|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| **Rekap nilai** | **87** | **6** | **-81 (-93.1%)** | **1.380 ms** | **148 ms** | **9.3x Lebih Cepat** | 21.4 MB | 6.2 MB (-71.0%) |
| **Dashboard dosen** | **24** | **4** | **-20 (-83.3%)** | **340 ms** | **55 ms** | **6.2x Lebih Cepat** | 8.2 MB | 5.2 MB (-36.6%) |
| **Dashboard mahasiswa** | **16** | **4** | **-12 (-75.0%)** | **210 ms** | **45 ms** | **4.7x Lebih Cepat** | 6.5 MB | 4.8 MB (-26.2%) |
| **Daftar pengumpulan** | **9** | **6** | **-3 (-33.3%)** | **88 ms** | **64 ms** | **1.4x Lebih Cepat** | 5.6 MB | 5.0 MB (-10.7%) |
| **Detail mata kuliah** | **7** | **6** | **-1 (-14.3%)** | **68 ms** | **52 ms** | **1.3x Lebih Cepat** | 5.1 MB | 4.8 MB (-5.9%) |
| **Daftar mata kuliah** | **5** | **5** | **0 (Terkontrol)** | **42 ms** | **38 ms** | **1.1x Lebih Cepat** | 4.5 MB | 4.3 MB (-4.4%) |
| **Daftar tugas** | **4** | **4** | **0 (Terkontrol)** | **35 ms** | **30 ms** | **1.2x Lebih Cepat** | 4.2 MB | 4.0 MB (-4.8%) |

> **Pencapaian Kunci:** Halaman **Rekap Nilai** berhasil diturunkan dari 87 query menjadi **6 query** (target modul < 15 query tercapai telak), dengan pemangkasan waktu eksekusi sebesar **89.3%** dan penghematan memori RAM sebesar **71.0%**.

---

### 3. Audit dan Optimasi Frontend Blade
* **Prinsip `loading="lazy"`:**  
  Sesuai arahan modul 11.1, elemen gambar dioptimasi menggunakan atribut `loading="lazy"` untuk menunda rendering gambar di luar viewport:
  ```html
  <img src="..." alt="..." loading="lazy">
  ```
* **Audit Antarmuka KampusLMS:**  
  Antarmuka web EduKampus memanfaatkan ikon berbasis font vektor **Google Material Symbols** (`<span class="material-symbols-outlined">...</span>`) dan avatar inisial nama berbasis CSS/SVG murni (`{{ strtoupper(substr($user->name, 0, 1)) }}`). Hal ini menghemat permintaan aset bitmap eksternal secara drastis.
* **Pembersihan Logika Kueri dari Blade:**  
  Memastikan seluruh view Blade **bersih dari query Eloquent langsung** (seperti pemanggilan `Model::all()` di dalam template). Seluruh data disiapkan dan diproyeksikan dari controller.
* **Penjagaan State Pagination:**  
  Seluruh tautan pagination dipastikan mempertahankan parameter filter pencarian menggunakan `{{ $items->withQueryString()->links() }}`.

---

### 4. Finalisasi Dokumen Master Proyek ([`docs/performa.md`](../performa.md))
Menyatukan seluruh data pengukuran awal (baseline), analisis N+1, pembuktian `EXPLAIN`, dokumentasi indeks database, strategi caching, dan tabel perbandingan sebelum vs sesudah optimasi ke dalam berkas master [`docs/performa.md`](../performa.md) untuk memenuhi seluruh rubrik Milestone M3 (Minggu 11).

---

## 4. Tahap 4: CHECKPOINT MINGGU 11 (Persiapan Sesi Wawancara Dosen)

### 1. Buka `docs/performa.md`. Halaman mana yang terburuk sebelum optimisasi? Kenapa halaman itu?
* **Jawaban:** Halaman **Rekap Nilai (`/grades`)**, yang mencatatkan **87 query**, waktu respons **1.380 ms**, dan memori **21.4 MB**.
* **Penyebab:** Terjadi *nested loop* di Blade antara iterasi seluruh mahasiswa terdaftar $\times$ seluruh tugas. Tanpa eager loading bertingkat, Eloquent menjalankan kueri submission dan grade secara individual untuk setiap sel matriks.

### 2. Peragakan N+1 di aplikasi Anda: matikan `with()` pada satu halaman dan tunjukkan lonjakan query di Debugbar.
* **Peragaan:** Pada `CourseController@index`, jika klausa `with('lecturer')` dihapus, kueri melonjak dari **5 kueri menjadi 20 kueri** untuk 15 baris mata kuliah. Jika `Model::preventLazyLoading()` aktif, Laravel langsung melempar error `LazyLoadingViolationException`.

### 3. Apa beda `withCount('students')` dan `count($course->students)`? Kenapa yang kedua boros?
* **Jawaban:** `withCount('students')` dieksekusi langsung di MySQL menggunakan subquery `SELECT COUNT(*)`, sehingga hanya mengirim satu angka integer ke PHP tanpa membebani memori server. Sedangkan `count($course->students)` memuat seluruh objek User ke memori RAM server PHP baru kemudian dihitung.

### 4. Tunjukkan index yang Anda tambahkan. Jalankan `EXPLAIN` dan buktikan ia terpakai.
* **Jawaban:** Indeks `idx_submissions_user_id` pada tabel `submissions`. Pada perintah `EXPLAIN SELECT * FROM submissions WHERE user_id = 15;`, kolom `type` berubah dari **`ALL`** (105 baris dibaca) menjadi **`ref`** (hanya 3 baris dibaca).

### 5. Sebutkan satu data di aplikasi Anda yang TIDAK BOLEH di-cache. Kenapa?
* **Jawaban:** **Nilai Mahasiswa (`grades`) dan Status Pengumpulan Tugas (`submissions`)**. Data akademik menuntut konsistensi instan (*strong consistency*). Caching akan menyebabkan data basi (*stale data*), sehingga pembaruan nilai oleh dosen tidak langsung terlihat oleh mahasiswa.

### 6. Apa yang terjadi pada `env()` setelah `config:cache`? Kenapa ini penting minggu depan?
* **Jawaban:** Seluruh pemanggilan `env()` di luar direktori `config/*.php` akan menghasilkan nilai **`null`**, karena Laravel berhenti membaca berkas fisik `.env` setelah cache konfigurasi aktif. Hal ini sangat penting untuk Minggu 12 (Deployment) agar server produksi tidak mengalami crash error 500.
