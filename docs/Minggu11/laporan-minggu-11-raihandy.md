# Laporan Praktikum Minggu 11 — Optimisasi Performa
### Nama: Raihandy Wijaya
### NIM: 10241064
### Kelompok: Kelompok 06  

---

## 1. Tahap 1: READ — Audit Seluruh Controller API & API Resources Terkait N+1 & Pagination (40 Menit)

Sesuai pembagian tugas pada [`konsep/Pembagian/pembagian-tugas-minggu-11.md`](../../konsep/Pembagian/pembagian-tugas-minggu-11.md), peran saya sebagai **Fullstack & REST API** bertanggung jawab mengaudit performa REST API v1, memastikan tidak ada kueri N+1 tersembunyi (*hidden lazy loading*) yang lolos saat data dikonsumsi klien eksternal (aplikasi mobile / SPA), menegakkan pagination 15 item/halaman, serta membersihkan seluruh pemanggilan fungsi `env()` liar sebelum deployment ke server produksi pada Minggu 12.

### 1.1 Tabel Hasil Audit Seluruh Endpoint REST API v1

Berikut adalah hasil audit pada seluruh controller API di folder `app/Http/Controllers/Api/`:

| Method & Endpoint | Controller & Action | Strategi Eager Loading | Pagination | Status Audit N+1 |
|---|---|---|:---:|:---:|
| `GET /api/v1/courses` | `CourseController@index` | `with('lecturer')`, `withCount(['materials', 'assignments'])` | 15 item/hal | ✅ Bebas N+1 |
| `GET /api/v1/courses/{id}` | `CourseController@show` | `load('lecturer')`, `loadCount(['materials', 'assignments'])` | Single resource | ✅ Bebas N+1 |
| `GET /api/v1/courses/{id}/materials` | `CourseController@materials` | `with('uploader')` | Koleksi penuh | ✅ Bebas N+1 |
| `GET /api/v1/courses/{id}/assignments` | `CourseController@assignments` | `with('creator')`, `withCount('submissions')` | 15 item/hal | ✅ Bebas N+1 |
| `POST /api/v1/assignments` | `AssignmentController@store` | `load(['course', 'creator'])` | Single resource | ✅ Bebas N+1 |
| `PUT /api/v1/assignments/{id}` | `AssignmentController@update` | `loadMissing('course')`, `load(['course', 'creator'])` | Single resource | ✅ Bebas N+1 |
| `DELETE /api/v1/assignments/{id}` | `AssignmentController@destroy` | `loadMissing('course')` | 204 No Content | ✅ Bebas N+1 |
| `GET /api/v1/assignments/{id}/submissions` | `AssignmentController@submissions` | `with(['student', 'grade.grader'])` | 15 item/hal | ✅ Bebas N+1 |
| `POST /api/v1/assignments/{id}/submissions` | `SubmissionController@store` | `loadMissing('course')`, `load(['student', 'assignment'])` | Single resource | ✅ Bebas N+1 |
| `PUT /api/v1/submissions/{id}/grade` | `SubmissionController@grade` | `loadMissing('assignment.course')`, `load('grader')` | Single resource | ✅ Bebas N+1 |
| `GET /api/v1/notifications` | `NotificationController@index` | Kolom langsung, tanpa relasi luar | 15 item/hal | ✅ Bebas N+1 |
| `POST /api/v1/notifications/{id}/read` | `NotificationController@read` | Kolom langsung | Single resource | ✅ Bebas N+1 |

---

### 1.2 Audit API Resources: Proteksi Mutlak via `whenLoaded()` dan `whenCounted()`

Pada arsitektur API Laravel, query N+1 sering kali tidak terlihat di controller, melainkan tersembunyi di dalam **API Resource**. Ketika properti relasi diakses secara langsung (contoh: `'lecturer' => new UserResource($this->lecturer)`), Eloquent akan mengeksekusi kueri tersembunyi (*silent lazy load*) untuk setiap item pada koleksi JSON jika relasi belum di-eager load.

Untuk memutus potensi N+1 secara permanen, seluruh API Resource telah diaudit dan diproteksi:
1. **`CourseResource.php`**: Menggunakan `$this->whenLoaded('lecturer')` dan `$this->whenCounted('materials')`, `$this->whenCounted('assignments')`. Jika relasi tidak dimuat oleh controller, atribut otomatis dihilangkan dari payload respons JSON tanpa memicu kueri tambahan.
2. **`AssignmentResource.php`**: Menggunakan `$this->whenLoaded('course')`, `$this->whenLoaded('creator')`, dan `$this->whenCounted('submissions')`.
3. **`SubmissionResource.php`**: Menggunakan `$this->whenLoaded('student')`, `$this->whenLoaded('assignment')`, dan `$this->whenLoaded('grade')`.
4. **`GradeResource.php`**: Menggunakan `$this->whenLoaded('grader')` dan `$this->whenLoaded('submission')`.
5. **`MaterialResource.php`**: Menggunakan `$this->whenLoaded('uploader')`.

---

## 2. Tahap 2: BREAK — Analisis Bahaya Pemanggilan `env()` Liar & Eksperimen `config:cache` (45 Menit)

### 2.1 Mengapa Pemanggilan `env()` di Luar `config/` Menjadi Penyebab Crash di Server Produksi?

Di lingkungan pengembangan lokal (*local development*), setiap kali fungsi `env('NAMA_KUNCI')` dipanggil, Laravel membaca isi berkas fisik `.env` secara dinamis. Kode apa pun yang memanggil `env()` akan tetap berjalan normal di laptop pengembang.

Namun, pada server produksi (tahap deployment Minggu 12), perintah wajib optimasi performa dijalankan:

```bash
php artisan config:cache
```

#### Apa yang Sebenarnya Terjadi Saat `config:cache` Dijalankan?
1. Laravel memindai seluruh berkas konfigurasi di folder `config/*.php`.
2. Semua nilai dievaluasi (termasuk fungsi `env()` yang berada di dalam `config/*.php`) dan digabungkan menjadi **satu berkas array PHP terkompilasi** di `bootstrap/cache/config.php`.
3. Framework **tidak lagi memuat berkas `.env` ke memori**.
4. Pustaka *vlucas/phpdotenv* tidak diinisialisasi. Akibatnya, seluruh pemanggilan fungsi `env('KUNCI')` di luar direktori `config/` (seperti di Controller, Model, Seeder, atau Blade) akan **selalu mengembalikan nilai `null`**!

#### Simulasi Dampak Nyata Kerusakan:
Jika sebuah controller memanggil `$apiKey = env('PAYMENT_API_KEY')`, saat konfigurasi di-cache, `$apiKey` bernilai `null`. Aplikasi akan crash dengan pesan *Invalid API Key* atau *Null Pointer Exception* di server produksi, meskipun di laptop pengembang lulus pengujian 100%.

---

### 2.2 Skrip Deteksi Pemanggilan `env()` Liar

Untuk mendeteksi seluruh pemanggilan `env()` yang tidak sah di luar folder `config/`, dijalankan skrip audit berbasis PowerShell di terminal proyek:

```powershell
Get-ChildItem -Path "app", "resources", "routes", "database" -Recurse -Filter "*.php" | Select-String -Pattern "env\("
```

#### Hasil Temuan Deteksi:
```text
kampuslms-kelompok-06\database\seeders\DemoAccountSeeder.php:21:
$password = env('DEMO_PASSWORD', 'ChangeMe@Prod2025!');
```

Ditemukan **1 pemanggilan `env()` liar** pada `database/seeders/DemoAccountSeeder.php` baris ke-21. Jika seeder ini dijalankan di server produksi setelah `php artisan config:cache`, nilai kata sandi demo akan menjadi `null` atau jatuh ke nilai default hardcoded, mengabaikan konfigurasi rahasia produksi yang disetel administrator.

---

## 3. Tahap 3: Kontribusi Implementasi Raihandy Wijaya

1. **Sanitasi Pemanggilan `env()` Liar ke `config()`:**
   * Mendaftarkan konfigurasi kata sandi demo `'demo_password' => env('DEMO_PASSWORD', 'ChangeMe@Prod2025!')` pada [`config/app.php`](../../config/app.php).
   * Mengganti pemanggilan `env()` pada [`database/seeders/DemoAccountSeeder.php`](../../database/seeders/DemoAccountSeeder.php) menjadi `config('app.demo_password')` agar nilai konfigurasi tidak `null` setelah `php artisan config:cache` di produksi (Minggu 12).
2. **Optimasi Endpoint REST API v1 & Eager Loading:**
   * **[`CourseController.php`](../../app/Http/Controllers/Api/CourseController.php):** Menerapkan eager loading `with('lecturer')`, `withCount(['materials', 'assignments'])`, serta eksplisit `select('courses.*')` dan `orderBy('courses.id')` pada pagination 15 item/halaman.
   * **Decoupling Relasi API Resources:** Memastikan seluruh API Resource ([`CourseResource`](../../app/Http/Resources/CourseResource.php), [`AssignmentResource`](../../app/Http/Resources/AssignmentResource.php), [`SubmissionResource`](../../app/Http/Resources/SubmissionResource.php), [`GradeResource`](../../app/Http/Resources/GradeResource.php), [`MaterialResource`](../../app/Http/Resources/MaterialResource.php)) menggunakan method `whenLoaded()` dan `whenCounted()` untuk mencegah *hidden lazy loading*.
3. **Mitigasi `LazyLoadingViolationException`:**
   * Menambahkan pemanggilan `$assignment->loadMissing('course')` pada otorisasi di [`AssignmentController.php`](../../app/Http/Controllers/Api/AssignmentController.php) dan method `store()` di [`SubmissionController.php`](../../app/Http/Controllers/Api/SubmissionController.php).
   * Menambahkan `$submission->loadMissing('assignment.course')` pada method `grade()` di [`SubmissionController.php`](../../app/Http/Controllers/Api/SubmissionController.php) agar relasi aman diperiksa tanpa melanggar proteksi `Model::preventLazyLoading()`.
4. **Verifikasi Optimasi Caching Produksi & Automated Test:**
   * Menjalankan perintah caching produksi: `php artisan config:cache`, `route:cache`, dan `view:cache`.
   * Memverifikasi seluruh pengujian otomatis: Test suite API (`tests/Feature/Api/`) **31 passed (94 assertions)** dan total pengujian aplikasi **84 passed (275 assertions), 0 failures**.

---

## 4. Tahap 4: CHECKPOINT — Jawaban Pertanyaan Interview Minggu 11

### 1. Halaman mana yang paling boros query sebelum dioptimasi? Mengapa?
**Jawaban:**  
Halaman **Rekap Nilai (`/grades`)**, yang mencatatkan **87 query** dengan waktu eksekusi **1.380 ms** pada kondisi baseline awal.  
*Penyebab:* Halaman ini menampilkan matriks relasi bertingkat berdimensi dua: perulangan seluruh mahasiswa terdaftar $\times$ seluruh tugas perkuliahan $\times$ pengumpulan tugas (submission) $\times$ catatan nilai (grade). Tanpa eager loading dan pengelompokan hash map di controller, setiap sel pada tabel Blade mengeksekusi kueri terpisah ke database MySQL (*N+1 query storm*).

### 2. Apa perbedaan mendasar antara `count($course->students)` dan `$course->withCount('students')`?
**Jawaban:**  
* `count($course->students)` mengeksekusi kueri `SELECT * FROM users INNER JOIN ...` yang memuat **seluruh kolom data dan instansiasi ratusan model User ke dalam memori RAM server PHP**, lalu menghitung jumlah elemen array menggunakan fungsi bawaan PHP `count()`. Hal ini sangat boros memori RAM dan memicu Full Table Scan.
* Sebaliknya, `$course->withCount('students')` dieksekusi langsung di level basis data MySQL menggunakan subquery `SELECT count(*) FROM users INNER JOIN ...`. Database hanya mengembalikan sebuah angka integer (`students_count`) ke PHP tanpa memuat baris data apa pun ke memori RAM server.

### 3. Apa akibat memanggil fungsi `env()` jika `php artisan config:cache` sudah dijalankan? Mengapa ini penting untuk Minggu 12?
**Jawaban:**  
Fungsi `env()` akan mengembalikan nilai **`null`** untuk setiap pemanggilan di luar direktori `config/*.php`.  
*Alasan:* Setelah perintah `config:cache` dieksekusi, Laravel menyatukan seluruh opsi konfigurasi ke dalam berkas `bootstrap/cache/config.php` dan sengaja tidak lagi membaca berkas fisik `.env` demi kecepatan booting aplikasi.  
*Urgensi Minggu 12 (Deployment):* Pada server produksi, `config:cache` wajib dijalankan. Jika masih ada pemanggilan `env()` di controller, model, atau view, aplikasi akan langsung mengalami crash (*HTTP 500 / error fatal*) saat diakses di domain publik, meskipun saat dites di komputer lokal berjalan normal.

### 4. Data apa yang tidak boleh di-cache? Mengapa?
**Jawaban:**  
Data **Nilai Mahasiswa (`grades`)** dan **Status Pengumpulan Tugas (`submissions`)**.  
*Alasan:* Data akademik memiliki karakteristik *strong consistency* dan berdampak langsung pada pengguna. Jika nilai di-cache selama 10 menit, mahasiswa akan melihat nilai lama (*stale data*) dan menyangka dosen belum menilai tugasnya. Demikian pula jika status submission di-cache, mahasiswa yang baru saja mengumpulkan tugas tidak akan melihat status "Terkumpul", sehingga memicu kepanikan dan pengunggahan berkas berulang kali.

