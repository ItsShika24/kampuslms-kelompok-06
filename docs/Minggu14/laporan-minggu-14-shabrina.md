# Laporan Praktikum Minggu 14 — Debugging dan Testing
## Nama: Shabrina Putri Az-Zahra
## NIM: 10241066
## Kelompok: 06 (EduKampus)
**Peran Utama:** Database & Async Lead  
**Repositori:** KampusLMS (Laravel 12)  
**Dokumen Terkait:** [`docs/debugging.md`](../debugging.md), [`tests/Feature/SubmissionUploadTest.php`](../../tests/Feature/SubmissionUploadTest.php), [`tests/Feature/NotificationAndQueueTest.php`](../../tests/Feature/NotificationAndQueueTest.php)

---

## 1. Tahap 1: READ — Membaca Kegagalan, Log, dan Stack Trace (30 Menit)

Sesuai panduan Modul 14 Subbab 14.3, dilakukan eksplorasi sistematis terhadap diagnosis kegagalan aplikasi web, penelusuran exception antrean latar belakang, dan pembacaan jejak kesalahan (*stack trace*):

### 1.1 Eksekusi Pengujian Otomatis Proyek
Perintah pengujian dijalankan melalui terminal:
```bash
php artisan test --filter=SubmissionUploadTest
php artisan test --filter=NotificationAndQueueTest
```
* **Hasil Pengamatan:**
  * Rangkaian pengujian pengumpulan & penilaian ([`SubmissionUploadTest.php`](../../tests/Feature/SubmissionUploadTest.php)): **11 passed (35 assertions)**.
  * Rangkaian pengujian antrean & notifikasi asinkron ([`NotificationAndQueueTest.php`](../../tests/Feature/NotificationAndQueueTest.php)): **9 passed (43 assertions)**.
  * Total suite submission, penilaian, dan antrean: **20 passed (78 assertions)** berjalan dalam waktu ~3.6 detik tanpa ada kegagalan (*0 failed*).

### 1.2 Simulasi Test Sengaja Gagal & Pembacaan Baris Kode `app/`
Sebuah pengujian sengaja digagalkan pada `SubmissionUploadTest` dengan mengubah ekspektasi otorisasi penilaian tugas mahasiswa dari `403` menjadi `200`:
```php
$response->assertStatus(200); // Seharusnya 403 Forbidden
```
* **Keluaran Kesalahan (*Output Error*):**
  ```text
  FAILED  Tests\Feature\SubmissionUploadTest > mahasiswa dilarang memberikan nilai pada submission
  Expected response status code [200] but received 403.

  at tests/Feature/SubmissionUploadTest.php:394
     390▕         $response = $this->actingAs($studentA)->post(route('submissions.grade', $submission), [
     391▕             'score'    => 100,
     392▕             'feedback' => 'Mencoba menilai sendiri/teman',
     393▕         ]);
  ➜  394▕         $response->assertStatus(200);
  ```
* **Analisis Reflektif:**  
  Dalam mendiagnosis kegagalan test, baris pertama yang harus dibaca dari jejak kesalahan adalah **baris yang menunjuk langsung ke berkas di dalam `tests/` atau `app/`**, bukan tumpukan jejak vendor framework Laravel (`vendor/laravel/framework/...`). Dari baris 394 di atas, langsung terlihat bahwa sumber kegagalan adalah ketidakcocokan ekspektasi kontrak status kode HTTP keamanan.

### 1.3 Penelusuran Exception pada `storage/logs/laravel.log`
Pemeriksaan log dilakukan pada berkas `storage/logs/laravel.log`:
* **Temuan Kasus Exception:** Ditemukan exception riil `InvalidArgumentException: Disk [] does not have a configured driver` saat fungsi `env('FILESYSTEM_DISK_PRIVATE')` mengembalikan `null` setelah `php artisan config:cache`, serta `PDOException: Connection Refused` saat daemon database lokal belum siap.
* **Cara Membaca Stack Trace yang Benar:**  
  Stack trace sering kali menghasilkan 50+ baris jejak pemanggilan fungsi internal Symfony dan Laravel kernel. Aturan baku yang diterapkan: **abaikan seluruh jejak `vendor/` dan cari baris pertama yang memuat path controller/listener proyek** (misal `app/Http/Controllers/SubmissionController.php:122` atau `app/Listeners/SendAssignmentNotification.php:44`). Di baris itulah logika aplikasi kelompok mulai menyimpang.

### 1.4 Pemantauan Kueri SQL & Antrean Asinkron
Dipasang pemantauan kueri dan antrean latar belakang pada `AppServiceProvider.php` dan `SendAssignmentNotification.php`:
```php
if (config('app.debug')) {
    \Illuminate\Support\Facades\DB::listen(function ($query) {
        \Illuminate\Support\Facades\Log::debug("[SQL Query] {$query->sql}", [
            'bindings' => $query->bindings,
            'time_ms'  => $query->time,
        ]);
    });
}
```
* **Hasil Pengamatan:** Saat dosen menerbitkan tugas baru, kueri pengambilan mahasiswa terbukti terhindar dari N+1 query karena listener menggunakan `$course->students()->get()` secara kolektif sebelum memanggil `Notification::send($students, ...)`, bukan melakukan query satu per satu di dalam loop.

---

## 2. Tahap 2: BREAK — Tujuh Percobaan Simulasi Kegagalan & Test Menipu (45 Menit)

Tujuh eksperimen dijalankan di branch lokal untuk menguji batas ketahanan sistem pengujian dan memahami fenomena *false-positive tests*:

| # | Skenario Percobaan | Yang Dilakukan | Hasil Pengamatan & Analisis Kritis |
|---|-------------------|----------------|-------------------------------------|
| **1** | **Test yang Menipu (*False Positive*)** | Menulis test submit tugas yang hanya mengecek `assertRedirect()`, lalu mengosongkan proses penyimpanan file di controller. | **Test TETAP HIJAU!** Meskipun file tidak pernah tersimpan di disk privat, redirect tetap terjadi. **Kesimpulan:** Test wajib memiliki *assertion* fisik storage `Storage::disk('local')->assertExists(...)` dan database `assertDatabaseHas(...)`. |
| **2** | **Pencabutan Otorisasi Penilaian (`Gate::authorize`)** | Mengomentari baris `Gate::authorize('grade', $submission)` pada `SubmissionController@grade`. | **Test `test_mahasiswa_dilarang_memberikan_nilai_pada_submission` langsung MERAH (FAIL)!** Test mendeteksi mahasiswa berhasil menilai tugas (menerima status 302 bukannya 403 Forbidden). Ini membuktikan test kami aktif mengunci keamanan. |
| **3** | **Simulasi Silent Queue Failure (Tanpa Worker)** | Menjalankan pembuatan tugas pada `QUEUE_CONNECTION=database` tanpa mengaktifkan daemon worker. | Notifikasi tidak pernah masuk ke tabel `notifications` mahasiswa meskipun controller sukses. Hal ini membuktikan perlunya `Notification::fake()` dan `Queue::fake()` agar pengujian terisolasi dan tidak bergantung pada proses worker luar. |
| **4** | **Manipulasi Validasi Batas Waktu (*Deadline Bypass*)** | Mengirimkan form pengumpulan tugas melewati `due_at` saat `allow_late = false`. | Request berhasil ditolak dengan flash session error `"Tenggat waktu pengumpulan telah berakhir."`, dan baris database terbukti tidak tersimpan (`assertDatabaseMissing('submissions')`). |
| **5** | **Pencabutan Pembersihan Berkas Fisik Lama Saat Resubmit** | Menghapus baris `Storage::disk('local')->delete($filePath)` pada controller saat resubmit. | Berkas lama tetap tertinggal di direktori private storage menjadi berkas yatim (*orphan file*), dan assertion `Storage::disk('local')->assertMissing($oldFilePath)` langsung gagal memicu error test. |
| **6** | **Eksploitasi IDOR Unduhan Submission Mahasiswa Lain** | Mahasiswa B mencoba mengakses URL unduhan submission milik Mahasiswa A (`/submissions/{submissionA}/download`). | Controller menolak akses dengan status `HTTP 403 Forbidden` melalui `SubmissionPolicy@view`, membuktikan perlindungan privasi berkas tugas antar-mahasiswa. |
| **7** | **Tertinggalnya Fungsi `dd()` pada Listener Antrean** | Menaruh `dd($event)` di dalam method `handle()` pada `SendAssignmentNotification.php`. | Worker antrean langsung *crash* seketika, proses serialisasi job gagal, dan seluruh notifikasi tertahan tanpa pernah terkirim. Inilah alasan mengapa `dd()` dilarang keras masuk Pull Request. |

---

## 3. Tahap 3: BUILD — Penguatan Test Suite Submission & Antrean Asinkron (60 Menit)

Sebagai pemegang peran **Database & Async Lead**, saya mengimplementasikan dan memverifikasi modul pengujian berikut:

### 3.1 Feature Test Pengumpulan & Penilaian Tugas ([`tests/Feature/SubmissionUploadTest.php`](../../tests/Feature/SubmissionUploadTest.php))
Rangkaian test terdiri atas **11 skenario pengujian (35 assertions)**:
1. `test_student_can_submit_assignment_with_valid_file_to_private_storage`: Memverifikasi unggahan berkas PDF ke disk privat `local` (`storage/app/private/submissions/...`) dengan nama acak.
2. `test_resubmitting_assignment_deletes_old_file_and_updates_submission`: Memverifikasi logika `updateOrCreate` agar submission lama diperbarui tanpa duplikasi data, dan berkas fisik lama terhapus dari storage (`Storage::assertMissing`).
3. `test_submission_past_deadline_rejected_if_allow_late_is_false`: Memastikan pengumpulan melewati batas waktu ditolak jika `allow_late = false`.
4. `test_submission_past_deadline_accepted_as_late_if_allow_late_is_true`: Memastikan pengumpulan melewati batas waktu diterima dengan flag `is_late = true` jika `allow_late = true`.
5. `test_submission_rejects_disallowed_file_extension`: Menguji penolakan berkas berekstensi ilegal (`.exe`) melalui validasi mimes form request.
6. `test_student_can_download_own_submission_file`: Memverifikasi mahasiswa dapat mengunduh berkas jawaban tugas miliknya sendiri secara aman.
7. `test_dosen_can_download_student_submission_file`: Memverifikasi dosen pengampu dapat mengunduh berkas jawaban mahasiswa yang terdaftar di kelasnya.
8. `test_other_student_cannot_download_submission_prevents_idor`: Mencegah serangan IDOR di mana mahasiswa lain ditolak saat mencoba mengunduh submission teman sekelasnya (`403 Forbidden`).
9. `test_unauthenticated_user_cannot_download_submission`: Pengguna tanpa login dialihkan ke halaman login saat mencoba mengakses endpoint unduhan berkas.
10. `test_dosen_pengampu_dapat_memberikan_nilai_dan_feedback_pada_submission`: Menguji operasi penilaian oleh dosen pengampu dan memverifikasi data tersimpan di tabel `grades` (`assertDatabaseHas`).
11. `test_mahasiswa_dilarang_memberikan_nilai_pada_submission`: Memastikan mahasiswa dilarang memberikan nilai pada tugas (`403 Forbidden`).

### 3.2 Feature Test Antrean & Notifikasi Asinkron ([`tests/Feature/NotificationAndQueueTest.php`](../../tests/Feature/NotificationAndQueueTest.php))
Rangkaian test terdiri atas **9 skenario pengujian (43 assertions)**:
1. `test_assignment_creation_dispatches_event_and_notifies_enrolled_students`: Memverifikasi pembuatan tugas baru mendispatch event dan menyimpan data notifikasi database mahasiswa.
2. `test_assignment_creation_dispatches_event_and_notifies_enrolled_students_via_notification_fake`: Menggunakan `Notification::fake()` untuk menegaskan bahwa notifikasi tugas baru hanya dikirim ke mahasiswa terdaftar (`assertSentTo`) dan tidak terkirim ke mahasiswa luar (`assertNotSentTo`).
3. `test_grading_submission_notifies_only_the_submission_owner`: Memverifikasi penilaian tugas memicu notifikasi hanya kepada pemilik submission.
4. `test_grading_submission_notifies_only_submission_owner_via_notification_fake`: Menggunakan `Notification::fake()` untuk memastikan notifikasi penilaian diterima oleh mahasiswa bersangkutan (`assertSentTo`) dan tidak bocor ke mahasiswa lain (`assertNotSentTo`).
5. `test_student_cannot_mark_other_students_notification_as_read`: Menguji perlindungan IDOR pada endpoint API penandaan notifikasi terbaca.
6. `test_student_cannot_mark_other_students_notification_as_read_via_web`: Menguji perlindungan IDOR pada endpoint Web penandaan notifikasi terbaca (`404/403`).
7. `test_async_assignment_listener_is_queued_using_queue_fake`: Menggunakan `Queue::fake()` untuk memastikan listener `SendAssignmentNotification` didorong ke antrean latar belakang (`Queue::assertPushed`).
8. `test_notification_payload_does_not_leak_sensitive_grade_data`: Menjamin bahwa kolom data notifikasi tidak membocorkan angka nilai (*score*) atau isi feedback.
9. `test_grades_recap_is_properly_scoped_by_role`: Memastikan halaman rekap nilai hanya menampilkan data sesuai hak akses peran (mahasiswa, dosen, admin).

### 3.3 Dokumen Investigasi Bug Sistematis ([`docs/debugging.md`](../debugging.md))
Telah disusun dokumen resmi setebal 260+ baris yang membedah protokol *5-Step Systematic Debugging* dan 3 studi kasus bug nyata kelompok:
* **Kasus 1:** *Silent failure* notifikasi akibat antrean database menumpuk saat worker daemon `php artisan queue:work` tidak aktif. Pencegahan: Supervisor daemon dan pengujian fake.
* **Kasus 2:** Helper `env()` mengembalikan nilai `null` di lingkungan produksi akibat pembacaan file `.env` dinonaktifkan oleh framework setelah eksekusi `php artisan config:cache`. Pencegahan: Migrasi seluruh konfigurasi ke `config/*.php` dan akses via `config()`.
* **Kasus 3:** Ledakan kueri SQL hingga 94 query (*N+1 problem*) pada rekap nilai serta berkas fisik yatim saat penghapusan mata kuliah bertingkat. Pencegahan: Eager loading bertingkat `with(...)`, `preventLazyLoading()`, dan pembersihan fisik berkas secara eksplisit.

---

## 4. Evaluasi Mandiri & Jawaban Checkpoint Minggu 14 (Subbab 14.4)

Berikut adalah jawaban reflektif atas 6 pertanyaan wajib evaluasi Checkpoint Minggu 14:

### 4.1 Peragaan Test Merah Saat Fitur Dihapus
* **Langkah Demonstrasi:** Saya membuka `app/Http/Controllers/SubmissionController.php:122`, menghapus baris `Gate::authorize('grade', $submission);`, dan menjalankan `php artisan test --filter=test_mahasiswa_dilarang_memberikan_nilai_pada_submission`. Test langsung **MERAH (FAIL)** karena mahasiswa berhasil mengakses endpoint penilaian (menerima status 302 bukannya 403 Forbidden). Setelah baris otorisasi dikembalikan, test kembali **HIJAU**. Hal ini membuktikan test kami bermakna dan aktif mengawal logika keamanan.

### 4.2 Mengapa `assertStatus(200)` Sering Menipu?
* **Alasan Teknis:** Kode status `200 OK` (atau `302 Redirect`) hanya menunjukkan bahwa server web berhasil mengembalikan response HTTP tanpa unhandled exception. Status 200 **tidak menjamin** bahwa berkas fisik benar-benar tersimpan di disk privat (bukan publik atau hilang), data submission tersimpan tanpa menduplikasi baris lama, atau job antrean benar-benar didorong ke worker. Oleh karena itu, pengujian wajib diperkuat dengan `assertDatabaseHas`, `Storage::disk('local')->assertExists`, serta `Notification::assertSentTo`.

### 4.3 Mengapa Test Jalur Ditolak (403) Jauh Lebih Krusial?
* **Alasan Teknis:** Test jalur sukses (*happy path*) hanya membuktikan bahwa fitur bekerja untuk skenario normal. Namun pada aplikasi kampus berbasis multi-peran, risiko terbesar adalah **kebocoran data dan pembobolan hak akses (IDOR/Privilege Escalation)**. Memastikan bahwa mahasiswa tidak bisa menilai tugasnya sendiri, tidak bisa mengunduh berkas jawaban mahasiswa lain, dan ditolak saat mengumpulkan tugas melewati tenggat waktu adalah jaminan mutlak integritas sistem akademik.

### 4.4 Apa Fungsi `RefreshDatabase` dan Mengapa Migrasi Harus Reversible?
* **Fungsi:** `RefreshDatabase` atau `DatabaseTransactions` memastikan bahwa setiap pengujian berjalan dalam database yang terisolasi dan bersih, baik melalui transaksi rollback in-memory atau migrasi ulang otomatis, sehingga tidak ada data sampah dari test sebelumnya yang memicu error duplikasi kunci unik.
* **Kebutuhan Reversible:** Jika migrasi tidak memiliki method `down()` yang valid, perintah rollback akan gagal, menyebabkan tabel tertinggal dan merusak eksekusi test-test berikutnya dengan pesan error `Table already exists`.

### 4.5 Studi Kasus Debugging (`docs/debugging.md`)
* **Kasus Helper `env()`:** Saat deployment ke produksi, seluruh konfigurasi di-cache menggunakan `php artisan config:cache`. Akibatnya fungsi `env('FILESYSTEM_DISK_PRIVATE')` mengembalikan `null` di controller karena Laravel menonaktifkan pemuatan berkas `.env` setelah dicache demi kecepatan. Kami mempersempit masalah dengan membaca log dan memeriksa `config/filesystems.php`, lalu mengganti seluruh pemanggilan `env()` langsung di controller menjadi `config('filesystems.disks.local')`.

### 4.6 Mengapa `dd()` Dilarang di PR Sedangkan `Log::info()` Boleh?
* **Alasan:** `dd()` (*Dump and Die*) menghentikan seketika eksekusi skrip PHP dan mencetak seluruh data memori mentah ke layar peramban. Jika lolos ke produksi, pengguna biasa akan melihat layar debug hitam yang membocorkan kredensial database dan merusak pengalaman pengguna. Sebaliknya, `Log::info()` atau `Log::error()` menulis pesan diagnostik ke berkas log server di latar belakang tanpa mengganggu alur tampilan pengguna maupun menghentikan worker antrean.
