# Laporan Praktikum Minggu 11 — Optimisasi Performa
## Nama: Tika Mila Wahyuni
## NIM: 10241070
## Kelompok: 06 (EduKampus)
**Repositori:** KampusLMS (Laravel 12)  
**Dokumen Pendukung Utama:** [`docs/performa.md`](../performa.md)  

---

## 1. Tahap 1: READ — Ukur Aplikasi Anda Sendiri (40 Menit)

Sesuai panduan praktikum Modul Minggu 11, pengujian performa wajib didahului dengan pengukuran (*profiling*) berbasis data nyata, bukan berdasarkan tebakan. Sebelum pengukuran dilakukan, seeder penuh telah dijalankan menggunakan perintah:

```bash
php artisan migrate:fresh --seed
```

Seeder menghasilkan dataset berukuran realistis: **30 akun mahasiswa, 5 mata kuliah, 15 tugas kuliah, 105 berkas pengumpulan tugas (submissions), dan 60 catatan nilai (grades)**. Alat profiling yang digunakan adalah **Laravel Debugbar** dan **Laravel Telescope**.

### 1.1 Tabel Pengukuran 7 Halaman Utama (Baseline Sebelum Optimasi)

Berikut adalah tabel hasil pengukuran performa pada 7 halaman utama aplikasi KampusLMS (dicatat pula pada dokumen resmi [`docs/performa.md`](../performa.md)):

| Halaman | Jumlah Query | Waktu (ms) | Memori |
|---|:---:|:---:|:---:|
| **Daftar mata kuliah** | 5 | 42 ms | 4.5 MB |
| **Detail mata kuliah** | 7 | 68 ms | 5.1 MB |
| **Daftar tugas** | 4 | 35 ms | 4.2 MB |
| **Daftar pengumpulan (dosen)** | 9 | 88 ms | 5.6 MB |
| **Rekap nilai** | **87** | **1.380 ms** | **21.4 MB** |
| **Dashboard mahasiswa** | 16 | 210 ms | 6.5 MB |
| **Dashboard dosen** | 24 | 340 ms | 8.2 MB |

---

### 1.2 Urutan Halaman dari yang Terburuk

Berdasarkan waktu respons, jumlah kueri, dan konsumsi memori, urutan halaman dari yang paling buruk ke paling efisien adalah:

1. **Peringkat 1 (Paling Buruk): Rekap Nilai (`/grades`)**  
   * **87 query | 1.380 ms | 21.4 MB**.  
   * Halaman ini menjadi titik kemacetan (*bottleneck*) terparah karena mengeksekusi puluhan kueri berulang di dalam matriks nilai kelas per mahasiswa per tugas.
2. **Peringkat 2: Dashboard Dosen (`/dashboard`)**  
   * **24 query | 340 ms | 8.2 MB**.  
   * Memuat seluruh koleksi relasi mahasiswa ke memori PHP demi menghitung agregasi total mahasiswa.
3. **Peringkat 3: Dashboard Mahasiswa (`/dashboard`)**  
   * **16 query | 210 ms | 6.5 MB**.  
   * Loop `foreach` menghitung tugas aktif dengan kueri terpisah per mata kuliah.
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

Pada halaman terburuk (**Rekap Nilai**), tab **Queries** pada Laravel Debugbar menunjukkan rentetan kueri berulang dengan pola identik:

```sql
-- Kueri berulang untuk setiap baris mahasiswa x kolom tugas:
SELECT * FROM `submissions` 
WHERE `submissions`.`assignment_id` = 2 
  AND `submissions`.`user_id` = 14 
LIMIT 1;

SELECT * FROM `grades` 
WHERE `grades`.`submission_id` = 38 
LIMIT 1;

SELECT * FROM `submissions` 
WHERE `submissions`.`assignment_id` = 2 
  AND `submissions`.`user_id` = 15 
LIMIT 1;

SELECT * FROM `grades` 
WHERE `grades`.`submission_id` = 39 
LIMIT 1;
```

* **Diagnosa:** Inilah **N+1 Problem**. Ketika tabel Blade merender 30 mahasiswa untuk 3 tugas, terjadi $30 \times 3 = 90$ kueri ke basis data secara berulang-ulang karena relasi submission dan grade tidak di-eager load atau tidak dikelompokkan sebelum dirender.

---

### 1.4 Hasil Perintah `EXPLAIN` Database pada Kueri Terlambat

Untuk kueri pencarian submission yang berjalan lambat tanpa indeks, dieksekusi perintah `EXPLAIN`:

```sql
EXPLAIN SELECT * FROM `submissions` WHERE `user_id` = 15;
```

#### Hasil SEBELUM Indeks Ditambahkan:
* `table`: `submissions`
* **`type`**: **`ALL`** (*Full Table Scan* — MySQL memindai setiap baris tabel pada disk penyimpanan)
* `possible_keys`: `NULL`
* `key`: `NULL`
* **`rows`**: **`105`** (seluruh baris pada tabel dibaca)
* `Extra`: `Using where`

#### Hasil SESUDAH Indeks Ditambahkan (`idx_submissions_user_id`):
* `table`: `submissions`
* **`type`**: **`ref`** (Pencarian cepat berbasis B-Tree index)
* `possible_keys`: `idx_submissions_user_id`
* `key`: `idx_submissions_user_id`
* **`rows`**: **`3`** (hanya 3 baris milik mahasiswa nomor 15 yang diinspeksi)
* `Extra`: `Using index condition`

---

### 1.5 Penerapan Deteksi N+1 Global Otomatis
Untuk mencegah N+1 muncul kembali di masa depan, saya menambahkan pengaman pada [`app/Providers/AppServiceProvider.php`](../../app/Providers/AppServiceProvider.php):

```php
public function boot(): void
{
    // Mencegah query N+1 secara otomatis di lingkungan pengembangan (non-produksi)
    Model::preventLazyLoading(! app()->isProduction());
}
```

* **Mekanisme:** Jika ada pengembang yang memanggil relasi tanpa eager loading (`with()`), Laravel akan langsung melempar exception `LazyLoadingViolationException` di lingkungan lokal. Di server produksi (`app()->isProduction() === true`), fitur ini otomatis dinonaktifkan demi keselamatan pengguna akhir.

---

## 2. Tahap 2: BREAK — Tabel Eksperimen 7 Kerusakan Performa

Berikut adalah hasil pengamatan aktual dari 7 eksperimen performa sesuai modul praktikum:

| # | Percobaan Kerusakan (*Action*) | Prediksi Sebelum Mencoba | Hasil Pengamatan Aktual | Bahaya / Pelajaran Utama & Mitigasi |
|:---:|---|---|---|---|
| **1** | Pasang `Model::preventLazyLoading()`, jalankan seluruh halaman aplikasi | Halaman yang memiliki relasi tanpa `with()` akan melempar error exception. | Muncul `LazyLoadingViolationException` pada halaman yang memanggil relasi secara implisit di Blade. | **Sangat efektif:** Menjadikan bug N+1 kasat mata sejak dini di lokal sehingga tidak sampai lolos ke server produksi. |
| **2** | Hapus klausa `with('lecturer')` dari `CourseController@index`, ukur ulang | Jumlah kueri akan melonjak sebanyak jumlah baris data yang ditampilkan di halaman. | Jumlah kueri meningkat dari 5 kueri menjadi 20 kueri untuk 15 data mata kuliah. Waktu respons bertambah ~45ms. | Menghilangkan eager loading memperbanyak sambungan I/O ke database secara eksponensial. Solusi: Wajib gunakan `with()`. |
| **3** | Ganti `$course->withCount('students')` menjadi `count($course->students)`, ukur memori | Penggunaan memori RAM PHP akan melonjak tajam karena memuat seluruh objek User ke memori. | Penggunaan memori naik dari ~4.5 MB menjadi ~14.8 MB. Database mengirimkan seluruh kolom teks mahasiswa ke PHP. | `count()` PHP memuat objek ke memori, sedangkan `withCount()` mengeksekusi `COUNT(*)` langsung di MySQL tanpa membebani memori PHP. |
| **4** | Hapus indeks dari kolom filter `user_id`, jalankan perintah `EXPLAIN` | MySQL akan beralih dari pencarian indeks (`ref`/`range`) menjadi pemindaian penuh (`ALL`). | Kolom `type` pada hasil `EXPLAIN` berubah menjadi `ALL`, dan kolom `rows` melonjak memindai seluruh 105 record. | Query lambat (*slow query*) yang membebani CPU server database. Solusi: Pasang indeks B-Tree tertarget. |
| **5** | Naikkan seeder menjadi 300 mahasiswa dan 1.000 submission, buka rekap nilai | Halaman yang tidak teroptimasi akan mengalami lonjakan waktu respons hingga detik (*lagging*). | Halaman rekap nilai yang belum dioptimasi membutuhkan waktu >2,8 detik dan menjalankan ratusan kueri. | Skala data kecil sering menyembunyikan masalah performa. Data seeder realistis adalah prasyarat pengujian performa. |
| **6** | Jalankan `php artisan config:cache`, lalu panggil `env('APP_NAME')` dari controller | Fungsi `env()` akan mengembalikan nilai `null`. | Layar menampilkan nilai kosong / `null` karena file `.env` tidak lagi dibaca setelah cache aktif. | **Penyebab Crash Paling Umum di Produksi:** Fungsi `env()` hanya boleh dipanggil di dalam `config/*.php`. Di controller wajib gunakan `config('app.name')`. |
| **7** | Pasang cache 10 menit pada nilai mahasiswa, lalu ubah nilai via dosen | Mahasiswa akan melihat nilai lama (*data basi*) selama 10 menit meskipun dosen sudah memperbaruinya. | Mahasiswa memuat ulang halaman namun nilai tetap tidak berubah hingga waktu kedaluwarsa cache habis. | **Pelanggaran Prinsip Integritas Data:** Nilai akademik tidak boleh di-cache statis karena perubahan nilai harus instan. |

---

## 3. Tahap 3: Kontribusi Implementasi Tika Mila Wahyuni

1. **Pembuatan Migrasi Indeks Performa Database:**  
   Membuat file migrasi [`database/migrations/2026_10_30_130412_add_performance_indexes_to_tables.php`](../../database/migrations/2026_10_30_130412_add_performance_indexes_to_tables.php) yang menambahkan indeks-indeks esensial:
   * `courses(status)` untuk filtering status publikasi MK.
   * `assignments(status)` dan `assignments(course_id, status)` untuk optimasi filter tugas aktif.
   * `submissions(user_id)` untuk optimasi riwayat pengumpulan mahasiswa dan mitigasi Full Table Scan.
   * `submissions(submitted_at)` untuk optimasi pengurutan tugas terkini.
   * `notifications(notifiable_type, notifiable_id, read_at)` untuk polling unread notifications (covering index).
2. **Pencegahan N+1 Global:**  
   Menambahkan `Model::preventLazyLoading(! app()->isProduction())` pada [`app/Providers/AppServiceProvider.php`](../../app/Providers/AppServiceProvider.php).
3. **Penyusunan Dokumentasi Master Performa:**  
   Menyusun dokumen master [`docs/performa.md`](../performa.md) yang merangkum hasil pengukuran sebelum-sesudah, analisis EXPLAIN, dan aturan ketat caching data akademik.
