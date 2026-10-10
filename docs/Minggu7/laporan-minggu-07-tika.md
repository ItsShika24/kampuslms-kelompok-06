## Nama: Tika Mila Wahyuni
## NIM: 10241070

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

## 3. FIX - w07

1. **Otorisasi Controller:** Menambahkan `Gate::authorize()` pada `CourseController` (create, store, edit, update, destroy) dan `SubmissionController` (show) sehingga URL tidak bisa ditembus langsung meskipun tombol disembunyikan di Blade.
2. **Perbaikan Policy:** Memperbarui `CoursePolicy` agar memeriksa peran dan kepemilikan (create: admin/dosen, update: admin/dosen pengampu, delete: admin).
3. **Penyaringan Daftar Kursus:** Menggunakan `match ($user->role)` pada `CourseController@index` agar admin melihat semua MK, dosen melihat MK yang diampu, dan mahasiswa melihat MK yang diambil.
4. **Pencegahan Mass Assignment:** Mengganti `$request->all()` dengan `$request->only(['name', 'email'])` pada `ProfileController@update` untuk mencegah eskalasi peran menjadi admin.
5. **Pencegahan Session Fixation:** Menambahkan `$request->session()->regenerate()` setelah login pada `AuthenticatedSessionController@store`.
6. **Pencegahan User Enumeration:** Menyeragamkan pesan error login untuk email tidak terdaftar dan password salah.
7. **Proteksi Route Dosen:** Menambahkan middleware `role:dosen` pada grup route `/dosen`.
8. **Eliminasi N+1 Policy:** Memperbaiki `SubmissionPolicy@view` dengan short-circuit checking dan `loadMissing('assignment.course')`.
9. **Kompatibilitas Laravel 12:** Mengganti `$this->authorize()` dengan `Gate::authorize()` pada `MaterialController`, serta mendaftarkan alias middleware `'role'` pada `bootstrap/app.php`.

## Bukti Pengujian cURL

### 1. Bukti IDOR Submission (`SubmissionController@show`)
Sebelum:
```bash
curl -i -b cookie_mhs_b.txt http://localhost:8000/mahasiswa/submissions/1
# Output: HTTP/1.1 200 OK (Mahasiswa B bisa melihat submission Mahasiswa A)
```

Sesudah:

```bash
curl -i -b cookie_mhs_b.txt http://localhost:8000/mahasiswa/submissions/1
# Output: HTTP/1.1 403 Forbidden
```

### 2. Bukti Proteksi Mass Assignment Role Profil

Sebelum:

Mengirim parameter role=admin mengubah peran pengguna di database menjadi admin. 

Sesudah:

```bash
curl -i -X PUT http://localhost:8000/profile \
  -b cookie_user.txt \
  -H "X-CSRF-TOKEN: <TOKEN>" \
  -d "name=Hacker&email=hacker@test.com&role=admin"
# Output: HTTP/1.1 302 Found (Nilai role di database diabaikan dan tetap peran semula)
```

### 3. Bukti Proteksi Route Dosen dari Mahasiswa

Sebelum:

```bash
curl -i -b cookie_mahasiswa.txt http://localhost:8000/dosen/courses/create
# Output: HTTP/1.1 200 OK (Mahasiswa bisa mengakses modul pembuatan MK dosen)
```

Sesudah:

```bash
curl -i -b cookie_mahasiswa.txt http://localhost:8000/dosen/courses/create
# Output: HTTP/1.1 403 Forbidden
```


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

