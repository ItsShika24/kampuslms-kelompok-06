# DOKUMEN KEAMANAN & MATRIKS OTORISASI (MILESTONE M2) — KAMPUSLMS

**Mata Kuliah:** SI2514024 — Pemrograman Web  
**Proyek:** KampusLMS (Kelompok 06)  
**Framework:** Laravel 12  
**Milestone:** M2 (Minggu 7 — Autentikasi, Otorisasi, dan Validasi Menyeluruh)

---

## 1. LANDASAN KONSEPTUAL KEAMANAN

### 1.1 Autentikasi vs Otorisasi
- **Autentikasi (*Authentication* / "Siapa Anda"):**
  Proses pembuktian identitas pengguna ke dalam sistem. Diimplementasikan melalui kredensial email dan kata sandi pada formulir login web (dengan `Auth::attempt()`, session, dan cookie terenkripsi) serta token *Bearer* Laravel Sanctum pada REST API.
- **Otorisasi (*Authorization* / "Apa yang Boleh Anda Lakukan"):**
  Proses penegakan hak akses terhadap sumber daya atau tindakan tertentu. Diimplementasikan melalui dua lapis:
  1. *Lapis Gerbang (Middleware Peran):* Memastikan hanya peran tertentu (`admin`, `dosen`, `mahasiswa`) yang dapat memasuki grup route tertentu.
  2. *Lapis Objek (Laravel Policy & Query Scoping):* Menutup celah *Insecure Direct Object Reference* (IDOR) dengan memeriksa relasi kepemilikan data spesifik sebelum operasi dieksekusi.

### 1.2 Hashing Kata Sandi vs Enkripsi
- Kata sandi pada KampusLMS **tidak dienkripsi**, melainkan **di-hash** secara satu arah menggunakan algoritma Bcrypt/Argon2 melalui Eloquent attribute cast `'password' => 'hashed'` di model `App\Models\User`.
- **Konsekuensi:** Nilai teks asli kata sandi tidak dapat dikembalikan atau didekripsi oleh siapa pun, termasuk administrator basis data. Oleh sebab itu, alur lupa kata sandi bekerja dengan mengirimkan token reset unik yang bertenggat waktu (`password_reset_tokens`), bukan mengirimkan kata sandi lama.

### 1.3 Proteksi Session Fixation & Keamanan Sesi
- Pada method `AuthController::login()`, perintah `request()->session()->regenerate()` dipanggil segera setelah `authenticate()` berhasil. Ini meregenerasi ID sesi baru sehingga penyerang yang mencoba menyuntikkan ID sesi lama (*Session Fixation attack*) tidak dapat membajak sesi pengguna setelah korban login.
- Pada method `AuthController::logout()`, dipanggil `Auth::guard('web')->logout()`, `request()->session()->invalidate()`, dan `request()->session()->regenerateToken()` untuk memastikan seluruh jejak sesi dan CSRF token dibersihkan total dari peramban.

### 1.4 Mengapa `@can` di Blade Tidak Cukup
- Direktif `@can` pada template Blade hanyalah komponen pengalaman pengguna (**UX**), yaitu menyembunyikan atau menampilkan tombol interaksi pada antarmuka.
- `@can` **BUKAN** mekanisme keamanan server-side. Penyerang dapat melewati tampilan Blade dengan mengirimkan HTTP Request langsung (misalnya via `curl`, Postman, atau DevTools).
- Keamanan hakiki selalu ditegakkan di sisi backend menggunakan `Gate::authorize(...)` di dalam Controller dan `$this->user()->can(...)` di dalam Form Request.

### 1.5 Penyaringan di Level Query vs Level View
- Mengambil seluruh data dari database (`Course::all()` atau `Submission::all()`) lalu menyaringnya di Blade view dengan `@if ($course->lecturer_id === auth()->id())` adalah **praktik fatal dan berbahaya**:
  1. *Kebocoran Data:* Data sensitif tetap ditarik ke memori server dan rawan bocor lewat serialization, pagination total, atau API.
  2. *Kerusakan Paginasi:* Jika 15 data diambil per halaman tetapi 14 data disembunyikan oleh `@if`, pengguna hanya melihat 1 baris di tabel meski tertulis 15 item.
  3. *Performa Buruk:* Menghabiskan memori server dan memicu query N+1.
- KampusLMS menerapkan penyaringan di **Level Query Database**:
  ```php
  $query = (match ($user->role) {
      'admin'     => Course::query(),
      'dosen'     => $user->taughtCourses(),
      'mahasiswa' => $user->courses(),
      default     => abort(403),
  })->with('lecturer');
  ```

---

## 2. MATRIKS TITIK RAWAN IDOR & MEKANISME PENUTUPANNYA

Berikut adalah tabel evaluasi lengkap titik rawan IDOR (*Insecure Direct Object Reference*) yang berhasil ditutup pada Milestone M2 (Minggu 7):

| # | Endpoint / Route | Parameter Model | Siapa yang Berhak Akses? | Skenario Serangan IDOR Jika Tanpa Pengamanan | Mekanisme Penutupan di Minggu 7 | Status Kode Respon Diharapkan |
|---|---|---|---|---|---|:---:|
| 1 | `GET /submissions/{submission}` | `{submission}` | • Mahasiswa pemilik submission<br>• Dosen pengampu MK<br>• Admin | Mahasiswa A mengganti angka ID di URL untuk menyontek dan melihat file tugas / nilai Mahasiswa B. | **Policy:** `SubmissionPolicy::view()`<br>Memverifikasi `$submission->user_id === $user->id` atau kepemilikan MK dosen pengampu. | 200 (Sah)<br>403 (Ilegal) |
| 2 | `POST /submissions/{submission}/grade` | `{submission}` | • Dosen pengampu MK terkait<br>• Admin | Dosen A mengubah atau memberi nilai mahasiswa pada mata kuliah yang diampu oleh Dosen B. | **Policy:** `SubmissionPolicy::grade()` & `GradePolicy::create/update()`<br>Memeriksa `$submission->assignment->course->lecturer_id === $user->id`. | 302/200 (Sah)<br>403 (Ilegal) |
| 3 | `GET /courses/{course}/edit`<br>`PUT /courses/{course}` | `{course}` | • Admin (semua MK)<br>• Dosen pengampu MK bersangkutan | Dosen A mengirimkan form / payload PUT untuk mengubah nama atau SKS mata kuliah milik Dosen B. | **Policy:** `CoursePolicy::update()`<br>Dipanggil lewat `Gate::authorize('update', $course)` & `UpdateCourseRequest::authorize()`. | 200/302 (Sah)<br>403 (Ilegal) |
| 4 | `DELETE /courses/{course}` | `{course}` | • Hanya Administrator | Dosen atau mahasiswa mengirim request DELETE untuk menghapus mata kuliah dari database. | **Policy:** `CoursePolicy::delete()`<br>`Gate::authorize('delete', $course)` memastikan `$user->role === 'admin'`. | 302 (Sah)<br>403 (Ilegal) |
| 5 | `POST /courses/{course}/assignments`<br>`GET /courses/{course}/assignments/create` | `{course}` | • Dosen pengampu MK terkait<br>• Admin | Dosen A menambahkan tugas baru pada kurikulum mata kuliah milik Dosen B. | **Policy:** `AssignmentPolicy::create()`<br>`Gate::authorize('create', [Assignment::class, $course])`. | 302 (Sah)<br>403 (Ilegal) |
| 6 | `GET /assignments/{assignment}/edit`<br>`PUT /assignments/{assignment}`<br>`DELETE /assignments/{assignment}` | `{assignment}` | • Dosen pengampu MK terkait<br>• Admin | Dosen lain mengubah tenggat waktu (*deadline*) atau menghapus tugas mata kuliah orang lain. | **Policy:** `AssignmentPolicy::update()` & `AssignmentPolicy::delete()`<br>Memverifikasi `$assignment->course->lecturer_id === $user->id`. | 200/302 (Sah)<br>403 (Ilegal) |
| 7 | `POST /assignments/{assignment}/submit` | `{assignment}` | • Mahasiswa yang terdaftar (*enrolled*) pada MK terkait | Mahasiswa dari luar kelas mengirim submission liar ke tugas mata kuliah yang tidak diikutinya. | **Policy:** `AssignmentPolicy::submit()`<br>Memverifikasi `$assignment->course->students()->whereKey($user->id)->exists()`. | 302 (Sah)<br>403 (Ilegal) |
| 8 | `POST /courses/{course}/materi`<br>`GET /courses/{course}/materi/create` | `{course}` | • Dosen pengampu MK terkait<br>• Admin | Dosen A mengunggah materi ke mata kuliah milik Dosen B. | **Policy:** `MaterialPolicy::create()`<br>`Gate::authorize('create', [Material::class, $course])`. | 302 (Sah)<br>403 (Ilegal) |
| 9 | `GET /materials/{material}/download` | `{material}` | • Mahasiswa terdaftar pada MK<br>• Dosen pengampu MK<br>• Admin | Mahasiswa luar kelas menebak ID materi privat/soal ujian untuk mengunduhnya. | **Policy:** `MaterialPolicy::download()`<br>`Gate::authorize('download', $material)` memeriksa keikutsertaan kelas / kepemilikan. | 200 (Sah)<br>403 (Ilegal) |
| 10 | `GET /admin/users`<br>`GET /admin/users/{user}`<br>`PUT /admin/users/{user}`<br>`DELETE /admin/users/{user}` | `{user}` | • Hanya Administrator (atau user melihat profil sendiri) | Mahasiswa atau dosen mengakses data profil pengguna lain, menaikkan peran menjadi admin, atau menghapus user. | **Policy:** `UserPolicy` & `EnsureUserHasRole('admin')`<br>`Gate::authorize` menutup akses non-admin. | 200 (Sah)<br>403 (Ilegal) |
| 11 | `GET /mata-kuliah` (Daftar MK) | — (Tingkat Daftar) | • Admin: semua MK<br>• Dosen: MK diampu<br>• Mahasiswa: MK diikuti | Mahasiswa/Dosen dapat melihat daftar mata kuliah yang tidak ada sangkut pautnya dengan mereka. | **Query Scoping:** `CourseController::index()`<br>Kueri `match ($user->role)` mengisolasi data di tingkat database SQL. | 200 |
| 12 | `GET /submissions` (Daftar Pengumpulan) | — (Tingkat Daftar) | • Admin: semua submission<br>• Dosen: MK diampu<br>• Mahasiswa: milik sendiri | Mahasiswa melihat daftar tugas dan submission rekan-rekannya di seluruh kampus. | **Query Scoping:** `SubmissionController::index()`<br>`where('user_id', $user->id)` untuk mahasiswa; `whereHas('lecturer_id')` untuk dosen. | 200 |

---

## 3. PANDUAN EKSEKUSI PENGUJIAN OTORISASI (SCRIPTS)

Pengujian otomatis keamanan dan otorisasi dapat dijalankan melalui dua kanal:

### 3.1 Otomatisasi Fitur (PHPUnit / Pest)
```bash
php artisan test --filter PolicyAuthorizationTest
php artisan test --filter WebAuthTest
```
*Hasil:* Seluruh test case otorisasi dan autentikasi berstatus **PASS (100% Hijau)**.

### 3.2 Skrip Pengujian Bash (`scripts/test-authz.sh`)
Skrip pengujian berbasis `curl` yang menguji skenario nyata dengan multi-akun:
```bash
bash scripts/test-authz.sh
```
Skrip ini mensimulasikan percobaan akses oleh 5 aktor:
1. Tamu / Unauthenticated (Pengunjung tanpa login)
2. Mahasiswa A (`mahasiswa@kampuslms.test`)
3. Mahasiswa B
4. Dosen A (`dosen@kampuslms.test` / Dr. Bambang Hermanto)
5. Dosen B (`siti.rahmawati@kampuslms.test` / Siti Rahmawati)
6. Admin (`admin@kampuslms.test` / Budi Santoso)

---

## 4. KESIMPULAN

Seluruh titik rawan IDOR dari rancangan awal Minggu 5 kini telah **ditutup sempurna** pada Minggu 7 (Milestone M2) dengan memanfaatkan kemampuan bawaan Laravel 12:
1. **Kebijakan Otorisasi Resmi (*Policies*):** Diterapkan pada 5 model inti (`Course`, `Material`, `Assignment`, `Submission`, `Grade`, plus `User`).
2. **Panggilan Terpusat (*Gate::authorize*):** Dipanggil konsisten di seluruh controller menggantikan pengecekan sementara `abort_unless`.
3. **Penyaringan Database (*Query Scoping*):** Seluruh metode `index()` menyaring data berdasarkan `auth()->user()->role` di tingkat SQL.
4. **Validasi Form (*Form Request*):** Metode `authorize()` memanggil `$this->user()->can(...)`.
5. **Tampilan Cerdas (*Blade Directives*):** Menggunakan `@can` untuk estetika UI tanpa mengorbankan keamanan server.
