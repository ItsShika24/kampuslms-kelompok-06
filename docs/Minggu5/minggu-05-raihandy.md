# LAPORAN MINGGU 5 — Routing Lanjutan, Model Binding, dan Middleware

**Mata Kuliah:** SI2514024 — Pemrograman Web  
**Proyek:** KampusLMS (Kelompok 06)  
**Framework:** Laravel 12  

---

## 1. READ — Daftar Titik Rawan IDOR (*Insecure Direct Object Reference*)

Berdasarkan analisis pemetaan route yang menerima parameter model (`{course}`, `{assignment}`, `{material}`, `{submission}`), berikut adalah evaluasi titik rawan IDOR pada sistem KampusLMS:

| Route / Endpoint | Parameter Model | Siapa yang Berhak Akses? | Risiko IDOR Jika Tanpa Pengecekan | Mekanisme Pencegahan (Lapis 1 — Minggu 5) |
|---|---|---|---|---|
| `GET /submissions/{submission}` | `{submission}` | 1. Mahasiswa pemilik submission<br>2. Dosen pengampu MK<br>3. Admin | Mahasiswa lain dapat membaca berkas jawaban, catatan, dan nilai mahasiswa lain hanya dengan mengubah angka ID di URL. | `abort_unless($sub->user_id === auth()->id() \|\| auth()->user()->role === 'admin' \|\| $sub->assignment->course->lecturer_id === auth()->id(), 403)` |
| `POST /submissions/{submission}/grade` | `{submission}` | Dosen pengampu MK terkait & Admin | Dosen A dapat memberi/mengubah nilai mahasiswa pada mata kuliah yang diampu Dosen B. | Pemeriksaan relasi `$submission->assignment->course->lecturer_id === auth()->id()` atau Admin. |
| `GET /courses/{course}/edit`<br>`PUT /courses/{course}` | `{course}` | 1. Admin (semua MK)<br>2. Dosen pengampu MK bersangkutan | Dosen A dapat mengubah deskripsi, SKS, atau konfigurasi mata kuliah milik Dosen B. | `abort_unless(auth()->user()->role === 'admin' \|\| (auth()->user()->role === 'dosen' && $course->lecturer_id === auth()->id()), 403)` |
| `DELETE /courses/{course}` | `{course}` | Hanya Admin | Dosen atau pengguna lain dapat menghapus mata kuliah dari database. | `abort_unless(auth()->user()->role === 'admin', 403)` |
| `POST /courses/{course}/assignments` | `{course}` | Dosen pengampu MK terkait & Admin | Dosen A dapat menambahkan tugas pada mata kuliah milik Dosen B. | `abort_unless($course->lecturer_id === auth()->id() \|\| auth()->user()->role === 'admin', 403)` |
| `GET/PUT/DELETE /assignments/{assignment}` | `{assignment}` | Dosen pengampu MK & Admin | Dosen lain dapat mengubah deadline atau menghapus tugas MK lain. | `abort_unless($assignment->course->lecturer_id === auth()->id() \|\| auth()->user()->role === 'admin', 403)` |
| `POST /assignments/{assignment}/submit` | `{assignment}` | Mahasiswa yang **terdaftar** pada MK | Mahasiswa dari luar kelas dapat mengirim submission ke tugas MK yang tidak diikutinya. | Validasi keikutsertaan kelas via tabel relasi: `auth()->user()->courses()->where('courses.id', $assignment->course_id)->exists()`. |
| `POST /courses/{course}/materials` | `{course}` | Dosen pengampu MK & Admin | Dosen A mengunggah materi ke mata kuliah milik Dosen B. | Pemeriksaan `$course->lecturer_id === auth()->id()` atau Admin. |
| `GET /materials/{material}/download` | `{material}` | Mahasiswa terdaftar, Dosen pengampu, & Admin | Mahasiswa luar dapat mengunduh materi privat/ujian milik kelas lain. | Validasi enrollment mahasiswa atau kepemilikan dosen pengampu. |
| `GET/PUT/DELETE /pengguna/{user}` | `{user}` | Hanya Admin (atau User melihat profilnya sendiri) | Pengguna biasa dapat mengedit nama, email, atau menaikkan role miliknya/orang lain. | Middleware `role:admin` pada grup route + otorisasi di controller. |

---

## 2. BREAK — Analisis Enam Kerusakan

1. **IDOR Nyata pada Submission:**
   - *Pengamatan:* Tanpa otorisasi di controller, mahasiswa A dengan ID 5 yang membuka `/submissions/41` dapat mengganti URL ke `/submissions/42` dan melihat submission mahasiswa lain.
   - *Pelajaran:* Route Model Binding hanya menjamin rekaman data ditemukan di database, tetapi **tidak menjamin** hak akses pengguna terhadap data tersebut.

2. **Nested Route Tanpa Scoping:**
   - *Pengamatan:* Mengakses `/courses/1/assignments/99` (di mana tugas ID 99 sebenarnya milik MK ID 7) tetap sukses menampilkan tugas jika tanpa scoping.
   - *Penyebab:* Parameter `{assignment}` dievaluasi langsung oleh binding global tanpa memverifikasi relasi kepemilikan dengan `{course}` di URL.

3. **Penerapan `Route::scopeBindings()`:**
   - *Pengamatan:* Setelah `Route::scopeBindings()` diaktifkan, URL `/courses/1/assignments/99` otomatis menghasilkan respons **404 Not Found**. Laravel memverifikasi relasi `$course->assignments()`.

4. **Pendaftaran Middleware di Laravel 12:**
   - *Pengamatan:* Mencari `app/Http/Kernel.php` berujung gagal karena berkas tersebut sudah dihilangkan di Laravel 12.
   - *Solusi:* Pendaftaran alias middleware dilakukan di `bootstrap/app.php` melalui fungsi `$middleware->alias([...])`.

5. **Akses Grup `role:admin` oleh Dosen:**
   - *Pengamatan:* Dosen yang mencoba mengakses route di dalam grup `role:admin` (seperti `/admin/users`) langsung dicegat dengan kode status **403 Forbidden**.

6. **Dosen A Mengedit Mata Kuliah Dosen B:**
   - *Pengamatan:* Middleware `role:dosen` berhasil meloloskan kedua dosen karena keduanya ber-role dosen. Jika controller tidak memeriksa `$course->lecturer_id === auth()->id()`, Dosen A berhasil mengedit data Dosen B.
   - *Kesimpulan Penting:* **Middleware saja tidak cukup untuk keamanan tingkat data (*object-level authorization*)!** Middleware memeriksa hak masuk gerbang peran, sedangkan Policy/Ownership check memeriksa izin terhadap objek data spesifik.

---

## 3. BUILD — Rincian Implementasi

### 1. Middleware `EnsureUserHasRole`
- Berkas: `app/Http/Middleware/EnsureUserHasRole.php`
- Menangani pemisahan status:
  - Belum login $\rightarrow$ **401 Unauthorized**
  - Login tetapi role salah $\rightarrow$ **403 Forbidden**
- Terintegrasi otomatis dengan simulasi role demo.

### 2. Pendaftaran Alias Middleware di `bootstrap/app.php`
```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->alias([
        'role' => \App\Http\Middleware\EnsureUserHasRole::class,
    ]);
})
```

### 3. Struktur Route Group Peran (`routes/web.php`)
- **Admin:** Prefix `/admin`, Name Prefix `admin.`, Middleware `role:admin`
  - `Route::resource('users', UserController::class);`
  - `Route::resource('courses', CourseController::class);`
- **Dosen:** Prefix `/dosen`, Name Prefix `dosen.`, Middleware `role:dosen`
  - `Route::resource('courses', CourseController::class)->only(['index', 'show', 'edit', 'update']);`
  - `Route::scopeBindings()->group(...)` dengan `->shallow()` untuk materi dan tugas.
- **Mahasiswa:** Prefix `/mahasiswa`, Name Prefix `mahasiswa.`, Middleware `role:mahasiswa`
  - Daftar & detail MK terdaftar, tugas, submit jawaban, unduh materi, dan submission pribadi.
- **Kompatibilitas:** Menyediakan route alias agar seluruh antarmuka Blade yang sudah dibuat tetap berfungsi mulus tanpa kendala.

### 4. Route Model Binding
- Seluruh controller (`CourseController`, `AssignmentController`, `MaterialController`, `UserController`, `SubmissionController`) menggunakan tipe model langsung pada parameter method (`show(Course $course)`, dll.), menggantikan pencarian ID manual `findOrFail($id)`.

### 5. Mitigasi IDOR Sementara (Lapis 1)
- Diterapkan menggunakan `abort_unless(...)` di setiap aksi pengubahan, penghapusan, dan pengunduhan data sensitif. Ini akan dirapikan menjadi Laravel Policy resmi pada Minggu 7.

### 6. Halaman Error 403 Kustom
- Berkas: `resources/views/errors/403.blade.php`
- Tampilan modern berbasis Tailwind CSS, informatif, navigasi kembali jelas, dan tidak membocorkan data pribadi pemilik objek.
