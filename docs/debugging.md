# DOKUMENTASI INVESTIGASI BUG & DEBUGGING SISTEMATIS
## Proyek: KampusLMS (EduKampus) — Laravel 12
**Mata Kuliah:** SI2514024 Pemrograman Web  
**Milestone:** Minggu 14 — Testing, Debugging & Integrasi Proyek  
**Penulis:** Shabrina Putri Azzahra (`10241066`) — Database & Async Lead  
**Kelompok:** Kelompok 06 (EduKampus)  

---

## 1. Pendahuluan & Metodologi Debugging

Dalam pengembangan sistem pembelajaran berbasis web (*Learning Management System*) skala kampus, kesalahan (*bugs*) tidak dapat dihindari sepenuhnya. Namun, perbedaan utama antara rekayasa perangkat lunak profesional dan coba-coba (*trial-and-error*) terletak pada **metodologi investigasi sistematis**.

Tim Kelompok 06 menerapkan protokol *5-Step Systematic Debugging*:
1. **Reproduksi Masalah (*Reproduce*):** Menciptakan skenario terkontrol untuk memicu bug secara konsisten, baik di lingkungan lokal maupun staging.
2. **Isolasi Masalah (*Isolate*):** Mempersempit area kesalahan dari lapisan terluar (HTTP Request/Web Server) ke Controller, Model/Database, atau Queue Worker.
3. **Analisis Akar Penyebab (*Root Cause Diagnosis*):** Meneliti mengapa kegagalan terjadi menggunakan log Laravel (`storage/logs/laravel.log`), Laravel Debugbar, SQL Query Logs, dan inspeksi status worker.
4. **Perbaikan Terstruktur (*Fix & Refactor*):** Menerapkan perbaikan di tingkat arsitektur (bukan tambal sulam sementara) tanpa merusak fungsionalitas lain (*backward compatibility*).
5. **Uji Regresi Otomatis (*Regression Testing*):** Menulis *automated feature test* (PHPUnit/Pest) yang secara permanen menjaga agar bug serupa tidak pernah muncul kembali di masa depan.

Dokumen ini merangkum **tiga studi kasus bug nyata** yang dihadapi tim selama siklus rilis Minggu 9 hingga Minggu 14, dampaknya, investigasi mendalam, serta solusi permanennya.

---

## 2. Studi Kasus 1: Notifikasi Gagal Terkirim Tanpa Pesan Error (Silent Queue Failure)

* **Waktu Kejadian:** Milestone M3 (Minggu 10 — Realtime, Event, Queue & Notifikasi)
* **Komponen Terdampak:** `AssignmentPublished`, `SendAssignmentNotification`, `NewAssignmentNotification`, Tabel `jobs`, Tabel `notifications`.
* **Klasifikasi Bug:** *Silent Background Failure* (Kegagalan Tanpa Jejak Error di Tampilan Web).

### 2.1 Gejala & Laporan Masalah
Saat dosen membuat tugas baru pada mata kuliah melalui form web (`POST /mata-kuliah/{course}/tugas`), sistem menampilkan pesan sukses `"Tugas berhasil diterbitkan"` dan halaman detail tugas terbuka secara normal. Namun, ketika akun mahasiswa terdaftar membuka lonceng notifikasi di navbar atau halaman `/notifications`, tidak ada notifikasi tugas baru yang muncul. Tidak ada pesan error HTTP 500, tidak ada peringatan validasi form, dan aplikasi terlihat sehat.

### 2.2 Investigasi & Pelacakan
1. **Pemeriksaan Database Notifikasi:**
   Dilakukan pengecekan pada tabel `notifications`:
   ```sql
   SELECT * FROM notifications WHERE notifiable_id = [id_mahasiswa];
   ```
   Hasil: *0 baris ditemukan*.
2. **Pemeriksaan Tabel Jobs:**
   Karena konfigurasi `.env` menggunakan `QUEUE_CONNECTION=database`, tabel `jobs` diperiksa:
   ```sql
   SELECT id, queue, payload, attempts, reserved_at FROM jobs;
   ```
   Hasil: Ditemukan baris job yang menumpuk di antrean `default`, dengan payload memuat `App\Listeners\SendAssignmentNotification`. Kolom `attempts` bernilai `0`.
3. **Analisis Log Aplikasi:**
   Pemeriksaan pada `storage/logs/laravel.log` tidak menunjukkan adanya exception atau error, karena job belum pernah dieksekusi sama sekali oleh worker.

### 2.3 Akar Penyebab (Root Cause)
Pada arsitektur asinkron Laravel, konfigurasi `QUEUE_CONNECTION=database` memindahkan pemrosesan listener yang mengimplementasikan `ShouldQueue` dari siklus request HTTP ke tabel antrean database. Jika proses worker (`php artisan queue:work`) tidak aktif di sistem latar belakang, job akan tersimpan secara statis di database selamanya tanpa pernah diproses. 

Di lingkungan lokal, kegagalan ini terjadi karena pengembang lupa menjalankan terminal worker terpisah. Di server produksi, kegagalan terjadi apabila worker mati (*crash*) akibat kehabisan memori atau reboot server tanpa ada pengawas proses (*process supervisor*).

### 2.4 Solusi & Langkah Pencegahan Permanen
1. **Penerapan Supervisor di Server Produksi (Minggu 12):**
   Dikonfigurasikan daemon Supervisor (`/etc/supervisor/conf.d/kampuslms-worker.conf`) untuk memastikan 2 proses worker selalu berjalan otomatis saat server boot dan melakukan *auto-restart* jika terjadi crash:
   ```ini
   [program:kampuslms-worker]
   process_name=%(program_name)s_%(process_num)02d
   command=php /var/www/kampuslms/artisan queue:work --sleep=3 --tries=3 --max-time=3600
   autostart=true
   autorestart=true
   user=www-data
   numprocs=2
   redirect_stderr=true
   stdout_logfile=/var/www/kampuslms/storage/logs/worker.log
   ```
2. **Penanganan Kegagalan & Ketahanan Job (*Resilience*):**
   Menambahkan konfigurasi percobaan (`$tries = 3`), jeda waktu (`$backoff = 10`), serta pencatatan log pada method `failed()` di listener:
   ```php
   class SendAssignmentNotification implements ShouldQueue
   {
       use InteractsWithQueue;
       public int $tries = 3;
       public int $backoff = 10;

       public function failed(\Throwable $exception): void
       {
           Log::error('SendAssignmentNotification gagal diproses di worker queue', [
               'error' => $exception->getMessage(),
           ]);
       }
   }
   ```
3. **Automated Feature Test dengan `Notification::fake()` & `Queue::fake()`:**
   Pengujian otomatis di `tests/Feature/NotificationAndQueueTest.php` dibuat tanpa bergantung pada proses worker fisik:
   ```php
   Notification::fake();
   $this->actingAs($lecturer)->post(route('tugas.store', $course), [...]);
   Notification::assertSentTo($enrolledStudent, NewAssignmentNotification::class);
   Notification::assertNotSentTo($unenrolledStudent, NewAssignmentNotification::class);
   ```

---

## 3. Studi Kasus 2: Helper `env()` Mengembalikan NULL Setelah Cache Produksi

* **Waktu Kejadian:** Milestone M4 (Minggu 11 & Minggu 12 — Optimisasi & Deployment)
* **Komponen Terdampak:** `SubmissionController.php`, `config/filesystems.php`, Penyimpanan Berkas Privat.
* **Klasifikasi Bug:** *Configuration Caching Side-Effect* (Aplikasi Berjalan di Lokal, Rusak di Produksi).

### 3.1 Gejala & Laporan Masalah
Fitur pengunduhan berkas privat tugas mahasiswa (`/submissions/{submission}/download`) berjalan 100% mulus saat diuji di laptop pengembang lokal. Namun, begitu proyek di-deploy ke server staging/produksi (VPS Ubuntu & Railway) dan skrip optimisasi dijalankan (`php artisan config:cache`), aksi pengunduhan berkas tiba-tiba memicu `HTTP 500 Internal Server Error` atau melempar pesan bahwa disk penyimpanan tidak terdefinisi.

### 3.2 Investigasi & Pelacakan
1. **Analisis Log Produksi:**
   Pemeriksaan `storage/logs/laravel.log` di server menunjukkan:
   ```
   [ERROR] InvalidArgumentException: Disk [] does not have a configured driver.
   in vendor/laravel/framework/src/Illuminate/Filesystem/FilesystemManager.php:152
   ```
   Nama disk yang dicari bernilai *string kosong* (`""`) atau `null`.
2. **Inspeksi Kode Pemanggil:**
   Ditemukan potongan kode lama pada controller/service yang mengambil konfigurasi storage langsung menggunakan helper environment:
   ```php
   // KODE BERMASALAH SEBELUM REFAKTOR:
   $diskName = env('FILESYSTEM_DISK_PRIVATE', 'local');
   Storage::disk($diskName)->download(...);
   ```
3. **Penyelidikan Mekanisme Internal Laravel:**
   Diuji di konsol artisan menggunakan `php artisan tinker`:
   * Sebelum `php artisan config:cache`: `env('FILESYSTEM_DISK_PRIVATE')` bernilai `'local'`.
   * Setelah `php artisan config:cache`: `env('FILESYSTEM_DISK_PRIVATE')` bernilai `null`!

### 3.3 Akar Penyebab (Root Cause)
Sesuai rancangan arsitektur framework Laravel (Modul Minggu 11.1 & 12.1), saat perintah `php artisan config:cache` dieksekusi, Laravel menggabungkan seluruh file konfigurasi di folder `config/*.php` ke dalam satu berkas terkompilasi `bootstrap/cache/config.php`. 

Untuk menghemat waktu I/O dan memori saat produksi (*performance boost*), Laravel **menonaktifkan pemuatan berkas `.env` secara keseluruhan**. Akibatnya, fungsi `env()` di luar berkas konfigurasi `config/` akan selalu mengembalikan nilai `null`. Kode yang memanggil `env()` secara langsung di Controller, Model, Policy, atau Service akan membaca nilai kosong dan menyebabkan sistem gagal.

### 3.4 Solusi & Langkah Pencegahan Permanen
1. **Pemindahan Konfigurasi ke Berkas Resmi:**
   Seluruh nilai konfigurasi dipusatkan di `config/filesystems.php`:
   ```php
   'disks' => [
       'local' => [
           'driver' => 'local',
           'root' => storage_path('app/private'),
           'throw' => false,
       ],
   ],
   ```
2. **Refaktor Kode: Penggantian `env()` Menjadi `config()`:**
   Semua pemanggilan `env()` di controller dihapus dan diganti menjadi helper resmi `config()`:
   ```php
   // KODE AMAN SETELAH REFAKTOR:
   Storage::disk('local')->download($submission->file_path, $submission->original_name);
   ```
3. **Pemberlakuan Standar Audit Kode Tim:**
   Menjalankan pemeriksaan otomatis pada repositori sebelum merge pull request untuk mendeteksi pemanggilan `env()` liar:
   ```bash
   grep -rn "env(" app/ routes/ resources/
   ```
   Jika ditemukan `env()` di luar folder `config/`, PR ditolak secara otomatis dalam proses code review.

---

## 4. Studi Kasus 3: N+1 Query Cascade Saat Rekap Nilai & Penghapusan Bersarang

* **Waktu Kejadian:** Minggu 11 & Minggu 13 (Optimisasi Performa & Pembersihan Database)
* **Komponen Terdampak:** `GradeController.php`, Halaman Rekap Nilai (`/grades`), Relasi `Course -> Assignments -> Submissions -> Grades`.
* **Klasifikasi Bug:** *N+1 Query Explosion & Orphan Physical Files*.

### 4.1 Gejala & Laporan Masalah
Pada saat dosen membuka halaman Rekap Nilai untuk mata kuliah dengan 30 mahasiswa dan 5 tugas (total 150 submission), waktu pemuatan (*load time*) melonjak hingga di atas 1,8 detik. Laravel Debugbar mencatat lonjakan drastis sebanyak **94 kueri SQL** hanya untuk satu halaman rekap nilai. Selain itu, ketika mata kuliah dihapus oleh admin, database menerapkan `cascadeOnDelete`, namun seluruh berkas fisik PDF di folder `storage/app/private/submissions/` tertinggal menjadi berkas yatim (*orphan files*) yang memboroskan kapasitas server.

### 4.2 Investigasi & Pelacakan
1. **Pemeriksaan Tab Queries di Laravel Debugbar:**
   Ditemukan query berulang dengan pola identik:
   ```sql
   SELECT * FROM assignments WHERE course_id = ?;
   SELECT * FROM submissions WHERE assignment_id = ?;
   SELECT * FROM grades WHERE submission_id = ?;
   SELECT * FROM users WHERE id = ?;
   ```
   Kueri di atas dijalankan berulang-ulang di dalam loop Blade (`@foreach ($courses as $course)`), memicu *N+1 query problem* bertingkat empat lapis.
2. **Inspeksi Penghapusan Berkas Fisik:**
   Saat `Course::destroy($id)` dipanggil, tabel relasi terhapus via foreign key cascade database MySQL. Namun, event Eloquent `deleting` pada model `Submission` tidak pernah dipanggil karena database engine menghapus baris secara langsung tanpa menginisialisasi model instance Laravel.

### 4.3 Akar Penyebab (Root Cause)
1. **Lazy Loading Tanpa Eager Loading:** Controller memanggil `Course::where('lecturer_id', $user->id)->get()` tanpa mendefinisikan relasi yang akan dirender di view (`assignments.submissions.grade`).
2. **Cascade di Level Basis Data Melewatkan Event Model:** Trigger database MySQL `ON DELETE CASCADE` berjalan di tingkat storage engine, sehingga lifecycle model Laravel (`deleting` event yang bertugas memanggil `Storage::disk('local')->delete($file)`) tidak pernah terpicu.

### 4.4 Solusi & Langkah Pencegahan Permanen
1. **Eager Loading Bersarang & `withCount`:**
   Controller diperbarui menggunakan `with()` bertingkat dan `loadMissing()`:
   ```php
   $courses = Course::where('lecturer_id', $user->id)
       ->with([
           'assignments' => fn ($q) => $q->orderBy('created_at'),
           'assignments.submissions' => fn ($q) => $q->with('student', 'grade'),
       ])
       ->get();
   ```
   Hasil: Jumlah query pada halaman rekap nilai turun drastis dari **94 query** menjadi **hanya 4–6 query** (penghematan >93%), dan waktu render berkurang menjadi di bawah 120 ms.
2. **Pencegahan Lazy Loading di Lingkungan Pengujian & Lokal:**
   Diaktifkan aturan keras pada `AppServiceProvider.php` (Modul 11.1):
   ```php
   public function boot(): void
   {
       Model::preventLazyLoading(! app()->isProduction());
   }
   ```
   Jika ada pengembang yang memanggil relasi tanpa eager loading di lokal, Laravel langsung melempar exception sehingga masalah terdeteksi sebelum masuk produksi.
3. **Pembersihan Berkas Fisik Sebelum Penghapusan Induk:**
   Pada controller penghapusan mata kuliah/tugas, dilakukan pembersihan berkas fisik secara eksplisit sebelum record induk dihapus:
   ```php
   foreach ($course->assignments as $assignment) {
       foreach ($assignment->submissions as $submission) {
           if ($submission->file_path && Storage::disk('local')->exists($submission->file_path)) {
               Storage::disk('local')->delete($submission->file_path);
           }
       }
   }
   $course->delete();
   ```

---

## 5. Ringkasan Perbandingan & Dampak Perbaikan

| Parameter Evaluasi | Sebelum Investigasi & Debugging | Setelah Perbaikan & Refaktor |
|---|---|---|
| **Keandalan Notifikasi** | Bergantung pada terminal manual, rentan *silent failure* | Terjamin dengan Supervisor daemon (auto-restart) & lolos uji `Notification::fake()` |
| **Stabilitas Deployment** | Rusak saat `config:cache` akibat helper `env()` liar | 100% stabil, seluruh konfigurasi aman melalui `config/*.php` |
| **Kueri SQL Rekap Nilai** | 94 query (N+1 bertingkat, latensi ~1.8s) | 4–6 query (Eager loaded, latensi ~110ms) |
| **Integritas Storage** | Berkas PDF yatim tertinggal saat mata kuliah dihapus | Bersih, berkas fisik dihapus otomatis dari disk privat |
| **Cakupan Automated Test** | Belum ada pengujian antrean asinkron | 20+ feature test otomatis lolos 100% HIJAU di CI GitHub Actions |

---

## 6. Kesimpulan & Best Practices untuk Tim

Dari pengalaman menyelesaikan ketiga bug kritis di atas, tim Kelompok 06 menetapkan prinsip baku dalam *Development Workflow*:
1. **Never Trust Silent Success:** Selalu uji proses latar belakang (*queue & event*) dengan verifikasi antrean database dan testing fake.
2. **Strict Config Rule:** Jangan pernah mengetik `env()` di luar folder `config/`.
3. **Always Eager Load Nested Relations:** Gunakan `preventLazyLoading()` di lingkungan lokal agar N+1 langsung terdeteksi.
4. **Test Before Merge:** Setiap perbaikan bug wajib disertai unit/feature test regresi agar bug yang sama tidak pernah berulang.
