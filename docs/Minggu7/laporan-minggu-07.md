# LAPORAN MINGGU 7 — Autentikasi, Otorisasi, dan Validasi Menyeluruh
## Milestone M2 (Tugas 2)

**Target:** Autentikasi Nyata, Pemisahan 3 Peran (Admin, Dosen, Mahasiswa), 5 Policy Lengkap, Mitigasi IDOR, Query-Level Scoping, dan Skrip Uji Keamanan.

---

## 1. READ — Bedah Mekanisme Autentikasi & Session Laravel 12

Pada Minggu 7, arsitektur KampusLMS diperkuat dari simulasi session sederhana menjadi **sistem autentikasi dan otorisasi produksi penuh**:

| Konsep | Mekanisme di KampusLMS | Alasan Keamanan / Best Practice |
|---|---|---|
| **Autentikasi** | `Auth::attempt($credentials)` di [AuthController.php](file:///c:/Users/Hype/Documents/TUGAS%20SEMESTER%205/PROWEB/kampuslms-kelompok-06/app/Http/Controllers/AuthController.php) | Memvalidasi kredensial (siapa Anda) menggunakan Bcrypt hashing. |
| **Otorisasi** | Laravel Policy + `Gate::authorize()` | Menentukan hak akses (apa yang boleh Anda lakukan) per objek model. |
| **Session Fixation Defense** | `$request->session()->regenerate()` | Mengganti Session ID lama setelah otentikasi sukses, mencegah penyerang membajak session yang telah disisipkan sebelum korban login. |
| **Logout Hygiene** | `Auth::logout()`, `$request->session()->invalidate()`, `$request->session()->regenerateToken()` | Menghapus seluruh data session dan meregenerasi CSRF token agar state bersih. |
| **Password Hashing** | Model Cast `'password' => 'hashed'` | Hashing satu arah (*one-way mathematical trapdoor*), sengaja lambat via Bcrypt guna mencegah serangan *brute force* massal. |
| **Pencegahan User Enumeration** | Generic flash error message | Mengembalikan satu pesan seragam: `"Email atau kata sandi yang Anda masukkan tidak sesuai."` baik untuk email tak terdaftar maupun password salah. |

---

## 2. BREAK — Analisis 8 Kerusakan Keamanan (Security Flaws)

| # | Skenario Kerusakan yang Diuji | Pengamatan Dampak & Risiko | Solusi di KampusLMS |
|---|---|---|---|
| 1 | Menghapus `session()->regenerate()` dari login | Penyerang menyusupkan ID session ke browser korban, saat korban login penyerang otomatis ikut masuk (*Session Fixation*). | Wajib memanggil `$request->session()->regenerate()` tepat setelah `Auth::attempt()`. |
| 2 | Menghapus `Gate::authorize()` di controller tapi membiarkan `@can` di Blade | **Tombol aksi di Blade hilang, tetapi URL/API tetap bisa diakses langsung via curl/Postman.** | Menegakkan `Gate::authorize()` di tingkat controller dan form request. `@can` hanya untuk kenyamanan antarmuka (UX). |
| 3 | Dosen A mengirim PUT/GET ke MK milik Dosen B | Dosen A dapat mengubah silabus, materi, atau tugas milik pengampu lain (*Broken Object Level Authorization / IDOR*). | Ditutup oleh `CoursePolicy::update` yang memvalidasi `$course->lecturer_id === $user->id`. |
| 4 | Mahasiswa membuka `GET /submissions/{id}` milik mahasiswa lain | Nilai, catatan dosen, dan berkas tugas mahasiswa lain terbongkar (*IDOR*). | Ditutup oleh `SubmissionPolicy::view` yang memeriksa `$submission->user_id === $user->id`. |
| 5 | Menggunakan `Course::paginate()` polos di `CourseController::index` | Mahasiswa dan dosen melihat seluruh data mata kuliah kampus, termasuk yang tidak diampu/diikuti (*List-Level Leakage*). | Query-level scoping menggunakan ekspresi `match ($user->role)` langsung di query builder. |
| 6 | Pengguna mengirimkan `role=admin` pada form request | Eskalasi hak akses menjadi administrator (*Privilege Escalation*). | Validasi ketat Form Request dan menghapus field `role` dari `$fillable` umum. |
| 7 | Menghapus cast `'password' => 'hashed'` | Kata sandi tersimpan sebagai plaintext di basis data. | Cast `'password' => 'hashed'` wajib aktif di model `User`. |
| 8 | Akses cookie session tanpa flag HttpOnly & Secure | Rentan dicuri melalui serangan XSS (*Cross-Site Scripting*). | Konfigurasi bawaan Laravel mengenkripsi cookie dan menyetel atribut HttpOnly. |

---

## 3. BUILD — Rincian Implementasi Milestone M2

### Langkah 1: Sistem Autentikasi Nyata
- **Controller:** [AuthController.php](file:///c:/Users/Hype/Documents/TUGAS%20SEMESTER%205/PROWEB/kampuslms-kelompok-06/app/Http/Controllers/AuthController.php) dengan proteksi Session Fixation (`session()->regenerate()`) dan generic error message.
- **Tampilan:** [resources/views/auth/login.blade.php](file:///c:/Users/Hype/Documents/TUGAS%20SEMESTER%205/PROWEB/kampuslms-kelompok-06/resources/views/auth/login.blade.php) menggunakan dark-mode premium, modern layout, dan pintasan 1-klik untuk akun demo (Admin, Dosen, Mahasiswa).
- **Rute & Middleware:** Rute `/login` dalam middleware `guest`, rute `/logout` dalam middleware `auth`.

### Langkah 2: Pembuatan 5 Policy Model Lengkap
Dibuat 5 Policy yang terdaftar otomatis (*auto-discovery*) di Laravel 12:
1. [CoursePolicy.php](file:///c:/Users/Hype/Documents/TUGAS%20SEMESTER%205/PROWEB/kampuslms-kelompok-06/app/Policies/CoursePolicy.php): Mengontrol izin `viewAny`, `view`, `create`, `update`, `delete`.
2. [MaterialPolicy.php](file:///c:/Users/Hype/Documents/TUGAS%20SEMESTER%205/PROWEB/kampuslms-kelompok-06/app/Policies/MaterialPolicy.php): Mengontrol akses unduh dan kelola materi.
3. [AssignmentPolicy.php](file:///c:/Users/Hype/Documents/TUGAS%20SEMESTER%205/PROWEB/kampuslms-kelompok-06/app/Policies/AssignmentPolicy.php): Mengontrol akses tugas, mencegah mahasiswa melihat tugas draft.
4. [SubmissionPolicy.php](file:///c:/Users/Hype/Documents/TUGAS%20SEMESTER%205/PROWEB/kampuslms-kelompok-06/app/Policies/SubmissionPolicy.php): Menutup titik rawan IDOR pengumpulan tugas.
5. [GradePolicy.php](file:///c:/Users/Hype/Documents/TUGAS%20SEMESTER%205/PROWEB/kampuslms-kelompok-06/app/Policies/GradePolicy.php): Menjamin hanya dosen pengampu atau admin yang berhak menilai.

### Langkah 3: Penegakan di Controller & Form Request
- Menggunakan `Illuminate\Support\Facades\Gate::authorize()` di seluruh controller:
  - [CourseController.php](file:///c:/Users/Hype/Documents/TUGAS%20SEMESTER%205/PROWEB/kampuslms-kelompok-06/app/Http/Controllers/CourseController.php)
  - [SubmissionController.php](file:///c:/Users/Hype/Documents/TUGAS%20SEMESTER%205/PROWEB/kampuslms-kelompok-06/app/Http/Controllers/SubmissionController.php)
  - [AssignmentController.php](file:///c:/Users/Hype/Documents/TUGAS%20SEMESTER%205/PROWEB/kampuslms-kelompok-06/app/Http/Controllers/AssignmentController.php)
  - [MaterialController.php](file:///c:/Users/Hype/Documents/TUGAS%20SEMESTER%205/PROWEB/kampuslms-kelompok-06/app/Http/Controllers/MaterialController.php)
- Penerapan Query-Level Scoping pada method `index()`:
  ```php
  $courses = (match ($user->role) {
      'admin'     => Course::query(),
      'dosen'     => $user->taughtCourses(),
      'mahasiswa' => $user->courses(),
      default     => abort(403),
  })->with('lecturer')->paginate(12);
  ```

### Langkah 4: Penerapan Direktif `@can` di Blade Views
- [resources/views/courses/index.blade.php](file:///c:/Users/Hype/Documents/TUGAS%20SEMESTER%205/PROWEB/kampuslms-kelompok-06/resources/views/courses/index.blade.php): Tombol "Tambah Mata Kuliah" dibungkus `@can('create', App\Models\Course::class)`.
- [resources/views/courses/show.blade.php](file:///c:/Users/Hype/Documents/TUGAS%20SEMESTER%205/PROWEB/kampuslms-kelompok-06/resources/views/courses/show.blade.php): Tombol "Edit MK", "Tambah Materi", dan "Tambah Tugas" dibungkus `@can('update', $course)`.
- [resources/views/submissions/show.blade.php](file:///c:/Users/Hype/Documents/TUGAS%20SEMESTER%205/PROWEB/kampuslms-kelompok-06/resources/views/submissions/show.blade.php): Form penilaian tugas dibungkus `@can('grade', $submission)`.

### Langkah 5: Dokumen Keamanan & Otomasi Skrip Uji
- **Dokumen Keamanan:** [docs/keamanan.md](file:///c:/Users/Hype/Documents/TUGAS SEMESTER 5/PROWEB/kampuslms-kelompok-06/docs/keamanan.md) berisi Tabel Titik Rawan IDOR 9 Skenario lengkap dan panduan interview.
- **Skrip Uji Otorisasi:** [scripts/test-authz.sh](file:///c:/Users/Hype/Documents/TUGAS SEMESTER 5/PROWEB/kampuslms-kelompok-06/scripts/test-authz.sh) yang menguji 26 skenario otorisasi Web & API secara otomatis dengan cURL.

---

## 4. CHECKPOINT — Persiapan Interview Tugas 2

1. **Apa beda autentikasi dan otorisasi? Tunjukkan satu contoh masing-masing di kode Anda.**
   - *Autentikasi (siapa Anda):* Diproses di `AuthController::login` menggunakan `Auth::attempt($credentials)`.
   - *Otorisasi (apa yang boleh Anda lakukan):* Diproses di `CoursePolicy::update` yang memeriksa apakah `$user->id === $course->lecturer_id`.
2. **Tunjukkan Policy yang Anda tulis. Jelaskan tiap barisnya.**
   - Buka `CoursePolicy.php`: Method `update` mengizinkan jika `$user->role === 'admin' || $course->lecturer_id === $user->id`. Baris ini mencegah dosen lain mengubah mata kuliah yang bukan miliknya.
3. **Kenapa `@can` di Blade tidak cukup? Peragakan dengan mengakses URL langsung.**
   - `@can` hanya menyembunyikan tombol di browser pengguna. Jika controller tidak memiliki `Gate::authorize()`, penyerang cukup mengirim request `PUT /mata-kuliah/{id}` lewat cURL untuk memodifikasi data. Keamanan sejati wajib berada di server-side.
4. **Buka `docs/keamanan.md`. Pilih satu baris, jelaskan bagaimana ia ditutup, lalu buktikan dengan curl.**
   - Skenario 1 (IDOR Submission): Mahasiswa A tidak boleh membuka submission Mahasiswa B (`GET /submissions/{id_B}`). Ditutup oleh `SubmissionPolicy::view`. Terbukti menghasilkan respons `403 Forbidden` pada skrip `test-authz.sh`.
5. **Kenapa kata sandi di-hash, bukan dienkripsi? Apa konsekuensinya untuk fitur lupa password?**
   - Hash bersifat satu arah (*one-way*), sehingga tidak dapat didekripsi kembali meskipun penyerang memiliki kunci enkripsi server. Konsekuensinya, sistem tidak bisa mengirimkan kata sandi lama kepada pengguna, melainkan harus mengirim tautan reset token untuk membuat kata sandi baru.
6. **Apa fungsi `session()->regenerate()` saat login?**
   - Menghasilkan ID session baru setelah login berhasil guna memitigasi serangan *Session Fixation*.
7. **Kenapa daftar mata kuliah tidak boleh diambil semua lalu disaring di view?**
   - Menyaring di view berarti database memuat seluruh data ke memori server (boros memori, lambat), pagination menjadi tidak akurat, dan data sensitif berisiko bocor jika ada kesalahan logika pada view template.
8. **Tunjukkan satu bagian yang Anda tulis dengan bantuan AI. Apa yang Anda ubah, dan kenapa?**
   - AI awalnya menghasilkan `$this->authorize()` pada controller (gaya Laravel 10). Karena di Laravel 12 trait `AuthorizesRequests` sudah dilepas dari kelas `Controller`, kode tersebut diubah menjadi `Gate::authorize()` agar kompatibel penuh dengan arsitektur Laravel 12 modern.

---

## 5. Ringkasan Hasil Pengujian

### 1. Skrip Uji Bash: `scripts/test-authz.sh`
```text
================================================================
   KAMPUSLMS — AUTHORIZATION & SECURITY TEST SUITE (MINGGU 7)   
   Target Web: http://127.0.0.1:8000                            
   Target API: http://127.0.0.1:8000/api/v1                     
================================================================
[PASS] Expected: 302 | Actual: 302 — Tamu mengakses GET /dashboard
[PASS] Expected: 302 | Actual: 302 — Tamu mengakses GET /mata-kuliah
[PASS] Expected: 302 | Actual: 302 — Tamu mengakses GET /admin/users
[PASS] Expected: 302 | Actual: 302 — Tamu mengakses GET /submissions
[PASS] Expected: 401 | Actual: 401 — Tamu memanggil API GET /api/v1/me
[PASS] Expected: 401 | Actual: 401 — Tamu memanggil API GET /api/v1/courses
[PASS] Expected: 403 | Actual: 403 — IDOR TEST: Mahasiswa mencoba membuka submission mahasiswa lain
[PASS] Expected: 200 | Actual: 200 — Mahasiswa membuka submission miliknya sendiri
[PASS] Expected: 403 | Actual: 403 — Mahasiswa mencoba membuka halaman tambah MK
[PASS] Expected: 403 | Actual: 403 — Mahasiswa mencoba POST membuat mata kuliah
[PASS] Expected: 403 | Actual: 403 — Mahasiswa mencoba mengakses modul pengguna (GET /admin/users)
[PASS] Expected: 403 | Actual: 403 — Mahasiswa mencoba memberi nilai pada submission
[PASS] Expected: 403 | Actual: 403 — Mahasiswa mencoba melihat tugas draft
[PASS] Expected: 403 | Actual: 403 — API: Mahasiswa mencoba menilai tugas via API
[PASS] Expected: 200 | Actual: 200 — Dosen A membuka detail mata kuliah miliknya
[PASS] Expected: 403 | Actual: 403 — IDOR TEST: Dosen A mencoba form edit MK milik Dosen B
[PASS] Expected: 403 | Actual: 403 — IDOR TEST: Dosen A mencoba PUT update MK milik Dosen B
[PASS] Expected: 403 | Actual: 403 — Dosen A mencoba mengakses modul pengguna admin
[PASS] Expected: 403 | Actual: 403 — Dosen A mencoba DELETE mata kuliah
[PASS] Expected: 403 | Actual: 403 — API: Dosen A membuka detail MK milik Dosen B via API
[PASS] Expected: 200 | Actual: 200 — Admin mengakses manajemen pengguna (GET /admin/users)
[PASS] Expected: 200 | Actual: 200 — Admin membuka mata kuliah Dosen A
[PASS] Expected: 200 | Actual: 200 — Admin membuka mata kuliah Dosen B
[PASS] Expected: 200 | Actual: 200 — Admin membuka daftar seluruh submission
[PASS] Session Fixation Protection: Session ID berubah setelah login (Regenerated)
[PASS] Generic Auth Error: Pesan error generik aktif, mencegah user enumeration

================================================================
  HASIL AKHIR: SELURUH PENGUJIAN OTORISASI LOLOS! (100% PASS)  
  Total: 26 | Lolos: 26 | Gagal: 0                     
================================================================
```

### 2. PHPUnit Feature & Unit Test Suite
```text
   PASS  Tests\Unit\ExampleTest
   PASS  Tests\Unit\PolicyTest
   PASS  Tests\Feature\Api\AssignmentApiTest
   PASS  Tests\Feature\Api\AuthApiTest
   PASS  Tests\Feature\Api\CourseApiTest
   PASS  Tests\Feature\Api\NotificationApiTest
   PASS  Tests\Feature\Api\SubmissionApiTest
   PASS  Tests\Feature\AuthWebTest
   PASS  Tests\Feature\AuthorizationWebTest
   PASS  Tests\Feature\ExampleTest
   PASS  Tests\Feature\ScopedBindingsTest

  Tests:    52 passed (152 assertions)
  Duration: 5.35s
```
Status: **100% HIJAU (Semua Pengujian Lolos)**
