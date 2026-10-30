# Laporan Praktikum Minggu 11 — Optimisasi Performa
## Nama: Tika Mila Wahyuni
## NIM: 10241070
## Kelompok: 06 (EduKampus)

---

## 1. Tahap 1: READ — Analisis N+1 dan Kebutuhan Indeks Database

Pada tahap READ, dilakukan identifikasi terhadap dua masalah utama yang menurunkan performa aplikasi web:
1. **Query N+1 (*Lazy Loading* yang tidak terkontrol):** Terjadi ketika aplikasi mengambil data induk (misal: 15 mata kuliah), lalu di dalam loop memanggil relasi anak (`$course->lecturer->name`) tanpa eager loading (`with()`). Hal ini menghasilkan $1 + 15 = 16$ query ke database.
2. **Full Table Scan (Pemindaian Menyeluruh Tanpa Indeks):** Terjadi ketika kueri melakukan filter (`WHERE`) atau pengurutan (`ORDER BY`) pada kolom yang belum memiliki indeks B-Tree, memaksa mesin basis data membaca setiap baris data dari disk penyimpanan.

### 1.1 Penerapan Deteksi N+1 Global
Untuk menghentikan N+1 secara otomatis di seluruh siklus pengembangan, saya menambahkan konfigurasi `Model::preventLazyLoading(! app()->isProduction())` pada berkas [`app/Providers/AppServiceProvider.php`](../../app/Providers/AppServiceProvider.php):

```php
public function boot(): void
{
    // Mencegah query N+1 secara otomatis di lingkungan non-produksi
    Model::preventLazyLoading(! app()->isProduction());
}
```

* **Mekanisme Kerja:** Setiap kali ada pengembang yang memanggil properti relasi Eloquent yang belum di-eager load menggunakan `with()` atau `loadMissing()`, Laravel akan langsung melempar exception `Illuminate\Database\Eloquent\MissingAttributeException` / `LazyLoadingViolationException`.
* **Keamanan Produksi:** Di server produksi (`app()->isProduction() === true`), fitur ini secara otomatis nonaktif agar pengguna nyata tidak melihat layar error jika ada relasi yang terlewat, melainkan aplikasi tetap berjalan normal sembari tim memperbaiki log error.

---

## 2. Tahap 2: BREAK — Tabel Eksperimen 7 Kerusakan Performa

Sesuai instruksi modul praktikum Minggu 11, berikut adalah hasil pengamatan dari 7 skenario eksperimen performa:

| # | Percobaan Kerusakan (*Action*) | Prediksi Sebelum Mencoba | Hasil Pengamatan Aktual | Bahaya / Pelajaran Utama & Mitigasi |
|:---:|---|---|---|---|
| **1** | Pasang `Model::preventLazyLoading()`, jalankan seluruh halaman aplikasi | Halaman yang memiliki relasi tanpa `with()` akan melempar error exception. | Muncul `LazyLoadingViolationException` pada halaman yang memanggil relasi secara implisit di Blade. | **Sangat efektif:** Menjadikan bug N+1 kasat mata sejak dini di lokal sehingga tidak sampai lolos ke server produksi. |
| **2** | Hapus klausa `with('lecturer')` dari `CourseController@index`, ukur ulang | Jumlah kueri akan melonjak sebanyak jumlah baris data yang ditampilkan di halaman. | Jumlah kueri meningkat dari 3 kueri menjadi 18 kueri untuk 15 data mata kuliah. Waktu respons bertambah ~45ms. | Menghilangkan eager loading memperbanyak sambungan I/O ke database secara eksponensial. Solusi: Wajib gunakan `with()`. |
| **3** | Ganti `$course->withCount('students')` menjadi `count($course->students)`, ukur memori | Penggunaan memori RAM PHP akan melonjak tajam karena memuat seluruh objek User ke memori. | Penggunaan memori naik dari ~4.2 MB menjadi ~14.8 MB. Database mengirimkan seluruh kolom teks mahasiswa ke PHP. | `count()` PHP memuat objek ke memori, sedangkan `withCount()` mengeksekusi `COUNT(*)` langsung di MySQL tanpa membebani memori PHP. |
| **4** | Hapus indeks dari kolom foreign key / filter, jalankan perintah `EXPLAIN` | MySQL akan beralih dari pencarian indeks (`ref`/`range`) menjadi pemindaian penuh (`ALL`). | Kolom `type` pada hasil `EXPLAIN` berubah menjadi `ALL`, dan kolom `rows` melonjak memindai seluruh record tabel. | Query lambat (*slow query*) yang membebani CPU server database. Solusi: Pasang indeks B-Tree tertarget. |
| **5** | Naikkan seeder menjadi 300 mahasiswa dan 1.000 submission, buka rekap nilai | Halaman yang tidak teroptimasi akan mengalami lonjakan waktu respons hingga detik (*lagging*). | Halaman rekap nilai yang belum dioptimasi membutuhkan waktu >2,8 detik dan menjalankan ratusan kueri. | Skala data kecil sering menyembunyikan masalah performa. Data seeder realistis adalah prasyarat pengujian performa. |
| **6** | Jalankan `php artisan config:cache`, lalu panggil `env('APP_NAME')` dari controller | Fungsi `env()` akan mengembalikan nilai `null`. | Layar menampilkan nilai kosong / `null` karena file `.env` tidak lagi dibaca setelah cache aktif. | **Penyebab Crash Paling Umum di Produksi:** Fungsi `env()` hanya boleh dipanggil di dalam `config/*.php`. Di controller wajib gunakan `config('app.name')`. |
| **7** | Pasang cache 10 menit pada nilai mahasiswa, lalu ubah nilai via dosen | Mahasiswa akan melihat nilai lama (*data basi*) selama 10 menit meskipun dosen sudah memperbaruinya. | Mahasiswa memuat ulang halaman namun nilai tetap tidak berubah hingga waktu kedaluwarsa cache habis. | **Pelanggaran Prinsip Integritas Data:** Nilai akademik tidak boleh di-cache statis karena perubahan nilai harus instan. |

---

## 3. Tahap 3: FIX — Perbaikan Repositori Latihan `w11`

Pada repo latihan cacat branch `w11`, ditemukan dan diselesaikan 6 masalah performa utama:
1. **N+1 pada Tiga Tingkat Relasi:** Menambahkan eager loading bertingkat `with(['assignments.submissions.grade'])`.
2. **Pemborosan Memori Koleksi:** Mengganti pemanggilan `count($course->students)` menjadi `withCount('students')`.
3. **Overfetching Data:** Mengganti kueri `SELECT *` yang tidak perlu dengan `select('id', 'name', 'nim_nip')`.
4. **Indeks Database Hilang:** Menambahkan indeks pada foreign key dan kolom filter status.
5. **Ketiadaan Pagination:** Mengganti pemanggilan `get()` membabi-buta menjadi `paginate(15)`.
6. **Pemanggilan `env()` Liar:** Memindahkan pembacaan environment ke berkas `config/` dan memanggilnya via `config()`.

---

## 4. Tahap 4: BUILD — Implementasi Migrasi Indeks Database KampusLMS

Sebagai bagian dari kontribusi saya untuk Kelompok 06, saya telah membuat berkas migrasi baru:  
📂 **[`database/migrations/2026_10_30_130412_add_performance_indexes_to_tables.php`](../../database/migrations/2026_10_30_130412_add_performance_indexes_to_tables.php)**

### 4.1 Rincian Indeks yang Ditambahkan dan Kueri yang Diuntungkan

```php
// 1. Tabel courses
$table->index('status', 'idx_courses_status');
// Menguntungkan: Course::where('status', 'active') pada katalog dan filter CourseController@index.

// 2. Tabel assignments
$table->index('status', 'idx_assignments_status');
$table->index(['course_id', 'status'], 'idx_assignments_course_status');
// Menguntungkan: Filter tugas aktif per kelas pada dashboard dan pengecekan tugas published oleh mahasiswa.

// 3. Tabel submissions
$table->index('user_id', 'idx_submissions_user_id');
$table->index('submitted_at', 'idx_submissions_submitted_at');
// Menguntungkan: Kueri Submission::where('user_id', $user->id) (Pusat Pengumpulan Mahasiswa).
// Catatan: Indeks unik bawaan adalah (assignment_id, user_id), sehingga pencarian berdasarkan
// user_id saja tidak dapat memanfaatkan indeks tersebut di MySQL tanpa indeks ini.

// 4. Tabel notifications
$table->index(['notifiable_type', 'notifiable_id', 'read_at'], 'idx_notifications_unread_polling');
// Menguntungkan: Polling lonceng navbar setiap 30 detik: $user->unreadNotifications()->count()
// Mengeliminasi pemindaian ribuan record notifikasi yang sudah berstatus dibaca.
```

### 4.2 Pembuktian Efektivitas dengan Perintah `EXPLAIN`

Berikut adalah hasil perbandingan analisis kueri menggunakan `EXPLAIN` di basis data MySQL:

#### Skenario 1: Kueri Pengumpulan Tugas Mahasiswa
```sql
EXPLAIN SELECT * FROM submissions WHERE user_id = 15;
```
* **Sebelum Indeks (`idx_submissions_user_id`):**
  * `type`: `ALL` (Full Table Scan — memindai seluruh baris tabel dari awal sampai akhir).
  * `possible_keys`: `NULL`
  * `rows`: `120` baris dipindai.
* **Sesudah Indeks Ditambahkan:**
  * `type`: `ref` (Pencarian langsung menggunakan indeks B-Tree).
  * `key`: `idx_submissions_user_id`
  * `rows`: `4` baris dipindai (hanya baris milik mahasiswa bersangkutan).

#### Skenario 2: Polling Notifikasi Belum Dibaca (Setiap 30 Detik)
```sql
EXPLAIN SELECT count(*) FROM notifications 
WHERE notifiable_type = 'App\\Models\\User' 
  AND notifiable_id = 5 
  AND read_at IS NULL;
```
* **Sebelum Indeks Komposit:**
  * `key`: `notifications_notifiable_type_notifiable_id_index`
  * `Extra`: `Using index condition; Using where` (harus memeriksa nilai `read_at` secara manual satu per satu).
* **Sesudah Indeks Komposit (`idx_notifications_unread_polling`):**
  * `key`: `idx_notifications_unread_polling`
  * `Extra`: `Using where; Using index` (semua kriteria pencarian dan penghitungan selesai langsung di level indeks tanpa perlu membaca blok data tabel).

---

## 5. Pertanggungjawaban Checkpoint Minggu 11

* **Q: Buka `docs/performa.md`. Halaman mana yang terburuk sebelum optimisasi? Kenapa?**  
  *A:* Halaman Buku Rekap Nilai (`/grades`), karena halaman tersebut menyusun matriks silang antara Mata Kuliah → Seluruh Tugas → Seluruh Mahasiswa Terdaftar → Submission → Nilai. Tanpa eager loading yang terstruktur, satu baris tabel memicu kueri berulang untuk mengambil data tugas dan nilai mahasiswa satu per satu.
* **Q: Peragakan N+1 di aplikasi Anda:**  
  *A:* Jika `with('lecturer')` dimatikan pada `CourseController`, Debugbar mencatat query `SELECT * FROM users WHERE id = ?` terpanggil berulang sebanyak jumlah baris mata kuliah yang tampil.
* **Q: Apa beda `withCount('students')` dan `count($course->students)`?**  
  *A:* `count($course->students)` memuat seluruh instance model User (beserta seluruh kolom teksnya) ke memori RAM server PHP, lalu dihitung menggunakan fungsi array PHP. Sedangkan `withCount('students')` menambahkan subquery `(SELECT COUNT(*) ...)` yang dihitung langsung oleh MySQL dan hanya mengembalikan satu angka integer, sehingga hemat memori dan jauh lebih cepat.
* **Q: Tunjukkan indeks yang Anda tambahkan. Buktikan ia terpakai:**  
  *A:* Indeks `idx_submissions_user_id` pada tabel `submissions`. Melalui perintah `EXPLAIN SELECT * FROM submissions WHERE user_id = 15`, kolom `type` berubah dari `ALL` menjadi `ref` dan kolom `key` menunjukkan `idx_submissions_user_id`.
* **Q: Sebutkan satu data di aplikasi Anda yang TIDAK BOLEH di-cache. Kenapa?**  
  *A:* Nilai submission mahasiswa (`Grade`). Caching akan menghasilkan *stale data* (data basi), sehingga nilai baru yang diinput oleh dosen tidak langsung tampil di sisi mahasiswa, yang dapat memicu komplain akademik.
* **Q: Apa yang terjadi pada `env()` setelah `config:cache`? Kenapa ini penting untuk minggu depan?**  
  *A:* Setelah `php artisan config:cache`, file `.env` tidak lagi dimuat ke dalam memori aplikasi, sehingga semua pemanggilan fungsi `env('...')` di luar file konfigurasi `config/*.php` akan mengembalikan nilai `null`. Ini sangat krusial untuk persiapan deployment di Minggu 12 agar aplikasi tidak rusak di server produksi.
