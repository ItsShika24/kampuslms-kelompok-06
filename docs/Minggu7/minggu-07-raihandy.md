# LAPORAN MINGGU 7 — Autentikasi, Otorisasi (Laravel Policies), dan Mitigasi IDOR (Milestone M2)

**Mata Kuliah:** SI2514024 — Pemrograman Web  
**Proyek:** KampusLMS (Kelompok 06)  
**Mahasiswa:** Raihandy Wijaya (NIM: 10241064)  
**Framework:** Laravel 12  
**Milestone:** M2 (Tugas 2 — Bobot 3%)  

---

## 1. READ — Konsep Autentikasi, Otorisasi, dan Pertahanan Berlapis (*Defense-in-Depth*)

Pada Minggu 7 (Milestone M2), fokus pengembangan adalah mengamankan seluruh arsitektur web KampusLMS dari otentikasi hingga otorisasi tingkat objek (*Object-Level Authorization*).

### A. Perbedaan Mendasar Autentikasi vs Otorisasi

| Dimensi | Autentikasi (*Authentication*) | Otorisasi (*Authorization*) |
|---|---|---|
| **Definisi** | Pembuktian identitas: *"Siapakah Anda?"* | Pemeriksaan wewenang: *"Apakah Anda berhak mengakses objek ini?"* |
| **Mekanisme** | Email + Password, Session Browser, Bearer Token | Role-Based Access Control (RBAC), Laravel Policies, Gate |
| **Kegagalan** | HTTP `401 Unauthorized` / Redirect ke `/login` | HTTP `403 Forbidden` / Peringatan larangan akses |
| **Lokasi Evaluasi** | Middleware Autentikasi (`auth`, `auth:sanctum`) | Laravel Policy (`$this->authorize()`, `Gate::authorize()`) |

### B. Arsitektur Pertahanan Berlapis (*Defense-in-Depth*) di KampusLMS

Untuk mencegah kebocoran data sensitif antar mahasiswa (*horizontal privilege escalation*) maupun antar dosen (*horizontal privilege escalation*), diterapkan 3 lapis pengamanan:

1. **Lapis 1 — Routing & Middleware (`EnsureUserHasRole`):**
   - Mengunci gerbang URL berdasarkan peran pengguna (`role:admin`, `role:dosen`, `role:mahasiswa`).
2. **Lapis 2 — SQL Query Scoping:**
   - Menyaring data langsung dari basis data pada method `index()` sehingga pengguna tidak pernah menerima baris data milik pengguna lain (misalnya mahasiswa hanya memuat mata kuliah yang diambilnya).
3. **Lapis 3 — Laravel Policy & Form Request:**
   - Memeriksa hak kepemilikan objek individual pada setiap aksi (`view`, `update`, `delete`, `grade`, `submit`).
4. **Lapis 4 — Antarmuka Blade (`@can` Directives):**
   - Tombol-tombol aksi (Edit, Hapus, Beri Nilai, Tambah) disembunyikan secara visual jika pengguna tidak memiliki izin, meminimalkan kebingungan antarmuka.

---

## 2. BREAK — Analisis Kerentanan & Vektor Serangan

Sebelum implementasi, dilakukan audit terhadap beberapa potensi celah keamanan:

1. **Session Fixation:**
   - *Celah:* Menggunakan ID session yang sama sebelum dan sesudah login memungkinkan penyerang membajak session korban jika ID session berhasil disuntikkan sebelumnya.
   - *Mitigasi:* Pemanggilan wajib `request()->session()->regenerate()` tepat setelah verifikasi kredensial berhasil pada `AuthController::login()`.
2. **Brute Force & Credential Stuffing:**
   - *Celah:* Tanpa rate limiting, endpoint login dapat dibombardir ratusan ribu request tebakan kata sandi.
   - *Mitigasi:* Pembatasan `RateLimiter::tooManyAttempts($throttleKey, 5)` (maksimal 5 percobaan per menit per kombinasi email + IP) pada `LoginRequest`.
3. **User Enumeration:**
   - *Celah:* Pesan error seperti "Email tidak ditemukan" membocorkan daftar pengguna valid ke pihak luar.
   - *Mitigasi:* Pesan error seragam `Email atau kata sandi yang Anda masukkan salah.` baik untuk email yang salah maupun kata sandi yang salah.
4. **IDOR Horizontal (Mahasiswa Mengintip Tugas Mahasiswa Lain):**
   - *Celah:* Mahasiswa A membuka `/submissions/15` milik Mahasiswa B.
   - *Mitigasi:* `SubmissionPolicy::view()` memastikan `$user->id === $submission->user_id` atau dosen pengampu atau admin.
5. **IDOR Antar Dosen (Dosen A Mengubah Mata Kuliah Dosen B):**
   - *Celah:* Dosen A memanggil `PUT /courses/2` yang diampu Dosen B.
   - *Mitigasi:* `CoursePolicy::update()` memvalidasi `$course->lecturer_id === $user->id` atau peran `admin`.

---

## 3. BUILD — Rincian Implementasi

### 1. Sistem Autentikasi Web Lengkap
- **Controller:** `app/Http/Controllers/AuthController.php`
  - `showLoginForm()`: Menampilkan antarmuka login.
  - `login()`: Mengotentikasi via `Auth::attempt()`, memicu `session()->regenerate()`, dan mengarahkan pengguna ke dashboard sesuai perannya.
  - `logout()`: Memanggil `Auth::logout()`, menginvalasi session (`$request->session()->invalidate()`), dan meregenerasi CSRF token (`$request->session()->regenerateToken()`).
  - `showForgotPasswordForm()`, `sendResetLink()`, `showResetPasswordForm()`, `resetPassword()`: Alur reset kata sandi menggunakan token hash aman `PasswordResetToken`.
- **Form Request:** `app/Http/Requests/LoginRequest.php`
  - Validasi email dan password.
  - Rate limiting berbasis cache string gabungan `Str::transliterate(Str::lower($this->input('email')).'|'.$this->ip())`.
- **Views:**
  - `resources/views/auth/login.blade.php`: Tampilan modern, kartu glassmorphism, responsive, dan quick demo credentials buttons.
  - `resources/views/auth/forgot-password.blade.php`: Form permohonan tautan reset.
  - `resources/views/auth/reset-password.blade.php`: Form pembaharuan kata sandi baru.

### 2. Lima Policy Utama + Policy Pengguna
Dibuat di bawah direktori `app/Policies/` memanfaatkan auto-discovery Laravel 12:

1. **`CoursePolicy.php`:**
   - `viewAny()`: Semua pengguna terautentikasi.
   - `view(User $user, Course $course)`: Admin, Dosen pengampu, atau Mahasiswa yang terdaftar (`enrollments`).
   - `create(User $user)`: Hanya `admin`.
   - `update(User $user, Course $course)`: `admin` atau Dosen pengampu (`$course->lecturer_id === $user->id`).
   - `delete(User $user, Course $course)`: Hanya `admin`.
2. **`MaterialPolicy.php`:**
   - `create()` & `update()` & `delete()`: Admin atau Dosen pengampu mata kuliah terkait.
   - `download()`: Mahasiswa terdaftar, Dosen pengampu, dan Admin.
3. **`AssignmentPolicy.php`:**
   - `create()`, `update()`, `delete()`: Admin atau Dosen pengampu.
   - `submit()`: Hanya Mahasiswa yang terdaftar pada mata kuliah terkait.
   - `viewSubmissions()`: Dosen pengampu dan Admin.
4. **`SubmissionPolicy.php`:**
   - `view()`: Mahasiswa pemilik submission, Dosen pengampu mata kuliah, dan Admin.
   - `grade()`: Dosen pengampu mata kuliah terkait dan Admin.
   - `delete()`: Admin atau Dosen pengampu.
5. **`GradePolicy.php`:**
   - `view()`: Mahasiswa pemilik, Dosen pengampu, dan Admin.
   - `create()` / `update()`: Dosen pengampu dan Admin.
6. **`UserPolicy.php`:**
   - Seluruh pengelolaan pengguna (CRUD) dikhususkan untuk peran `admin`.

### 3. Penerapan Otorisasi di Controller & Form Request
- **Controller Refactoring:**
  - `CourseController.php`, `AssignmentController.php`, `MaterialController.php`, `SubmissionController.php`, `UserController.php` secara konsisten memanggil `Gate::authorize('<ability>', $model)`.
  - Pada method `index()`, diterapkan **SQL query scoping** berbasis `match ($user->role)` sehingga tidak ada beban kebocoran data di level database.
- **Form Requests:**
  - `StoreCourseRequest`, `UpdateCourseRequest`, `StoreUserRequest`, `UpdateUserRequest` mengimplementasikan method `authorize()` yang memanggil `$this->user()?->can(...)`.

### 4. Penyempurnaan Antarmuka Blade
- Menggunakan direktif `@can('<ability>', $model)` pada:
  - `resources/views/courses/index.blade.php` (Tombol "Tambah Mata Kuliah").
  - `resources/views/courses/show.blade.php` (Tombol Edit, Hapus, Tambah Materi, Tambah Tugas).
  - `resources/views/assignments/show.blade.php` (Form Pengumpulan Tugas untuk Mahasiswa, Tombol Edit/Hapus untuk Dosen/Admin).
  - `resources/views/submissions/show.blade.php` (Form Penilaian Khusus Dosen/Admin).
  - `resources/views/components/layout.blade.php` (Menu Navigasi dinamis, info profil aktif, dan tombol Logout form POST).

### 5. Artefak Deliverable Keamanan & Pengujian
- **Dokumentasi Matriks IDOR:** Dibuat di `docs/keamanan.md` yang memetakan seluruh titik rawan model binding, deskripsi risiko, metode mitigasi Lapis 1 & Lapis 2, serta Policy method penanggung jawabnya.
- **Skrip Uji Otorisasi Otomatis:** Disediakan di `scripts/test-authz.sh` untuk melakukan simulasi request HTTP multi-role via curl terhadap 5 skenario otorisasi kritis.

---

## 4. VERIFIKASI — Pengujian & Validasi

### A. Automated Feature Tests (PHPUnit / Pest)
Dibuat dua berkas Feature Test baru untuk menguji seluruh skenario autentikasi dan otorisasi:
1. `tests/Feature/WebAuthTest.php` (9 Test Cases):
   - Tamu dapat melihat form login.
   - Pengguna terautentikasi dialihkan dari halaman login.
   - Login berhasil memperbarui session.
   - Login gagal dengan pesan seragam anti-enumeration.
   - Logout menginvalasi sesi.
   - Pengguna belum login dicegat dan dialihkan ke `/login`.
   - Alur reset password token berfungsi.
2. `tests/Feature/PolicyAuthorizationTest.php` (7 Test Cases):
   - Dosen B dilarang mengedit atau mengubah mata kuliah Dosen A (403 Forbidden).
   - Dosen A diizinkan mengubah mata kuliah miliknya sendiri (200 OK / Redirect).
   - Mahasiswa B dilarang melihat submission Mahasiswa A (403 Forbidden).
   - Dosen B dilarang menilai submission pada tugas Dosen A (403 Forbidden).
   - Mahasiswa dilarang memberi nilai submission (403 Forbidden).
   - Non-admin (dosen/mahasiswa) dilarang mengakses manajemen pengguna (403 Forbidden).
   - Admin memiliki akses penuh (*bypass/universal authority*).

### B. Hasil Eksekusi Test Suite
```bash
$ php artisan test

PASS  Tests\Unit\ExampleTest
PASS  Tests\Feature\Api\AssignmentApiTest (8 tests)
PASS  Tests\Feature\Api\AuthApiTest (6 tests)
PASS  Tests\Feature\Api\CourseApiTest (6 tests)
PASS  Tests\Feature\Api\NotificationApiTest (3 tests)
PASS  Tests\Feature\Api\SubmissionApiTest (8 tests)
PASS  Tests\Feature\ExampleTest (1 test)
PASS  Tests\Feature\PolicyAuthorizationTest (7 tests)
PASS  Tests\Feature\ScopedBindingsTest (3 tests)
PASS  Tests\Feature\WebAuthTest (9 tests)

Tests:    52 passed (141 assertions)
Duration: 5.88s
```

Seluruh 52 pengujian (141 assertions) berhasil lulus 100% tanpa kegagalan maupun error.

---

## 5. KESIMPULAN

Implementasi Minggu 7 berhasil menuntaskan seluruh kriteria Milestone M2 (Tugas 2):
1. Sistem autentikasi web berbasis session telah dilengkapi proteksi mutakhir (*Session Fixation mitigation*, *Rate Limiting*, dan *Anti-Enumeration*).
2. Tiga peran pengguna (`admin`, `dosen`, `mahasiswa`) memiliki hak akses yang terisolasi secara ketat dan konsisten baik di web maupun API.
3. Seluruh titik rawan IDOR berhasil ditutup menggunakan kombinasi SQL query scoping di level database dan Laravel Policies di level aplikasi.
