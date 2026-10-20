# Panduan & Pembagian Tugas BUILD Minggu 9
## Proyek: KampusLMS (Kelompok 06 — EduKampus)
**Mata Kuliah:** SI2514024 Pemrograman Web (Semester Ganjil 2026/2027)  
**Topik Minggu 9:** *Pengelolaan Unggahan Berkas, Penyimpanan Privat, dan Data Dinamis*  

---

## 🎯 Target Utama BUILD Minggu 9
1. **Unggah Materi oleh Dosen:** Mendukung PDF/PPTX/DOCX maksimal 20 MB pada disk privat `local`, atau alternatif tautan eksternal.
2. **Unduh Materi oleh Mahasiswa:** Hanya mahasiswa yang terdaftar di mata kuliah terkait yang boleh mengunduh (via Controller + Policy).
3. **Pengumpulan Tugas Mahasiswa:** Berkas disimpan dengan nama acak di disk privat, mencatat `original_name` dan `file_size`, menggunakan `updateOrCreate`, serta menolak pengumpulan jika lewat tenggat waktu (`allow_late == false`).
4. **Unduh Pengumpulan Tugas:** Hanya pemilik jawaban, dosen pengampu, dan admin yang berhak mengunduh berkas submission.
5. **Daftar & Penilaian Tugas:** Tampilan siapa yang sudah/belum mengumpulkan, label terlambat, dan tautan unduh berkas untuk dosen.
6. **Penghapusan Berkas Fisik:** Berkas fisik otomatis terhapus saat data dihapus (termasuk kasus cascade delete).
7. **Format Ukuran Ramah Manusia:** Menampilkan ukuran berkas seperti `2.4 MB` atau `512 KB`.

---

## 🌿 Aturan Git & Branch Tim
- Setiap anggota bekerja di branch masing-masing yang dibuat dari `main`.
- Jangan commit langsung ke `main`. Selalu buat Pull Request (PR) setelah selesai agar direview oleh rekan kelompok.
- Penamaan branch:
  - Tika: `Tika-Mila-Wahyuni-09` *(Sudah selesai & dibuatkan PR #22)*
  - Raihandy: `feature-w09-submit-raihandy` (atau `Raihandy-Wijaya-09`)
  - Oktavia: `feature-w09-ui-oktavia` (atau `Oktavia-Nur-Rahmadani-09`)
  - Shabrina: `feature-w09-validation-shabrina` (atau `Shabrina-Putri-Azzahra-09`)

---

## 1. Tika Mila Wahyuni
- **NIM:** 10241070  
- **Peran:** *Backend & Security Architecture (Private Storage, Download Controller & Model Lifecycle)*  
- **Status:** **SUDAH SELESAI (PR #22)**

### 📌 Tugas:
1. Memindahkan disk penyimpanan berkas materi di `MaterialController.php` dari disk `public` ke disk privat `local`.
2. Membuat method `download(Submission $submission)` di `SubmissionController.php` dengan proteksi `Gate::authorize('view', $submission)` dan pengiriman berkas dari `Storage::disk('local')->download()`.
3. Menambahkan model booted hook `deleting` di `Material.php` dan `Submission.php` untuk otomatis menghapus berkas fisik di disk `local` saat record database dihapus.
4. Menangani penghapusan berkas fisik pada method `destroy` di `CourseController.php` dan `AssignmentController.php` sebelum memicu cascade delete database.
5. Menambahkan accessor `formatted_file_size` pada model `Material` dan `Submission`.

### 📁 File Repo yang Diberikan ke AI:
- `app/Http/Controllers/MaterialController.php`
- `app/Http/Controllers/SubmissionController.php`
- `app/Models/Material.php`
- `app/Models/Submission.php`
- `app/Policies/SubmissionPolicy.php`

### 💬 Prompt untuk AI Tika:
```markdown
Konteks: Saya sedang mengerjakan modul Minggu 9 proyek KampusLMS (Laravel 12).
Tugas saya adalah memastikan penyimpanan berkas aman di disk privat dan menerapkan siklus hidup penghapusan berkas fisik.

Berikut kode controller dan model saya saat ini:
[Tempel isi MaterialController.php, SubmissionController.php, Material.php, dan Submission.php]

Tolong perbaiki dan buatkan kode untuk:
1. Ubah penyimpanan di MaterialController store() dan download() agar menggunakan disk privat 'local' (BUKAN 'public'), disimpan di folder 'materials/{course_id}' dengan nama acak.
2. Tambahkan method download(Submission $submission) di SubmissionController yang:
   - Memeriksa otorisasi menggunakan Gate::authorize('view', $submission).
   - Memeriksa keberadaan berkas fisik di disk 'local' (jika tidak ada, abort 404).
   - Mengembalikan response Storage::disk('local')->download($submission->file_path, $submission->original_name).
3. Tambahkan event model deleting di booted() pada Material.php dan Submission.php agar otomatis menghapus berkas fisik di disk 'local' saat record dihapus.
4. Tunjukkan cara aman menghapus berkas fisik saat Course atau Assignment dihapus (karena cascadeOnDelete di database tidak memicu event model Eloquent).
5. Buatkan accessor formatted_file_size di model Submission dan Material untuk format ukuran (B, KB, MB).

Syarat: Gunakan standar Laravel 12 (Gate::authorize, bukan $this->authorize). Berikan kode yang rapi dan siap pakai.
```

---

## 2. Raihandy Wijaya
- **NIM:** 10241064  
- **Peran:** *Fullstack & Backend Integrasi (Submission Upload Controller & API Integration)*  

### 📌 Tugas:
1. Memperbarui method `submit` di `AssignmentController.php` agar menerima berkas fisik (`$request->file('file')`), menyimpannya di disk `'local'` dengan path acak `submissions/{$assignment->id}`, dan mengisi field `file_path`, `original_name`, `file_size`.
2. Menghitung `is_late` di server berdasarkan waktu `now()->gt($assignment->due_at)`.
3. Menolak pengumpulan jika `now()->gt($assignment->due_at)` dan `$assignment->allow_late == false`.
4. Mendaftarkan route download submission di `routes/web.php` (`/submissions/{submission}/download` -> `SubmissionController@download`).
5. Memperbarui API Controller `app/Http/Controllers/Api/SubmissionController.php` agar endpoint `store` mendukung multipart file upload dan mengembalikan `SubmissionResource`.

### 📁 File Repo yang Diberikan ke AI:
- `app/Http/Controllers/AssignmentController.php`
- `routes/web.php`
- `app/Http/Controllers/Api/SubmissionController.php`
- `app/Models/Assignment.php`
- `app/Models/Submission.php`

### 💬 Prompt untuk AI Raihandy:
```markdown
Konteks: Saya sedang mengerjakan modul Minggu 9 proyek KampusLMS (Laravel 12).
Tugas saya adalah mengimplementasikan logika unggah pengumpulan tugas mahasiswa (web & REST API) serta integrasi alur submission.

Berikut kode AssignmentController.php, routes/web.php, dan Api/SubmissionController.php saya:
[Tempel AssignmentController.php, routes/web.php, dan app/Http/Controllers/Api/SubmissionController.php]

Tolong bantu implementasikan:
1. Perbarui method submit(Request $request, Assignment $assignment) di AssignmentController:
   - Validasi berkas: file wajib, mimes:pdf,doc,docx,zip, max:10240 (10 MB).
   - Otorisasi: Gate::authorize('submit', $assignment).
   - Validasi deadline: Jika now()->gt($assignment->due_at) dan !$assignment->allow_late, kembalikan back dengan error 'Tenggat waktu pengumpulan telah berakhir'.
   - Simpan berkas fisik ke disk privat 'local' dengan nama acak:
     $path = $request->file('file')->store("submissions/{$assignment->id}", 'local');
   - Gunakan Submission::updateOrCreate berdasarkan ['assignment_id' => $assignment->id, 'user_id' => $user->id].
   - Jika mahasiswa upload ulang, pastikan berkas fisik lama dihapus dari storage agar tidak menjadi file yatim.
   - Simpan field: file_path, original_name, file_size, note, submitted_at, is_late.
2. Pastikan route unduh submission di routes/web.php terdaftar dengan nama 'submissions.download'.
3. Perbarui juga Api/SubmissionController@store agar mendukung upload berkas multipart/form-data yang sama dan mengembalikan SubmissionResource.

Gunakan konvensi Laravel 12.
```

---

## 3. Oktavia Nur Rahmadani
- **NIM:** 10241060  
- **Peran:** *Frontend & UX (Form Upload, Human-Readable Size & Submission Views)*  

### 📌 Tugas:
1. Memperbarui view detail tugas di `resources/views/assignments/show.blade.php`:
   - Menambahkan atribut `enctype="multipart/form-data"` pada form submit.
   - Membuat antarmuka file picker/upload yang rapi menggunakan TailwindCSS.
   - Menambahkan petunjuk batas ukuran berkas (Maks 10 MB, format PDF/DOC/DOCX/ZIP).
   - Jika mahasiswa sudah mengumpulkan, tampilkan kartu informasi: nama berkas asli (`$submission->original_name`), ukuran berkas ramah manusia (`$submission->formatted_file_size`), badge status terlambat/tepat waktu, dan tombol "Unduh Berkas Saya" (`route('submissions.download', $submission)`).
2. Memperbarui tabel pengumpulan di `resources/views/submissions/index.blade.php` dan `resources/views/submissions/show.blade.php` agar dosen dapat melihat tombol unduh berkas tiap mahasiswa dengan jelas.
3. Memastikan form upload materi di `resources/views/materials/create.blade.php` sudah memiliki `enctype="multipart/form-data"`.

### 📁 File Repo yang Diberikan ke AI:
- `resources/views/assignments/show.blade.php`
- `resources/views/submissions/show.blade.php`
- `resources/views/submissions/index.blade.php`
- `resources/views/materials/create.blade.php`

### 💬 Prompt untuk AI Oktavia:
```markdown
Konteks: Saya mengerjakan sisi Frontend Blade modul Minggu 9 proyek KampusLMS (Laravel 12 + TailwindCSS).
Tugas saya adalah memperbarui antarmuka pengumpulan tugas, tampilan berkas yang diunggah, dan tombol unduhan untuk dosen & mahasiswa.

Berikut view saya saat ini:
[Tempel isi resources/views/assignments/show.blade.php dan resources/views/submissions/show.blade.php]

Tolong perbarui antarmukanya agar:
1. Form pengumpulan tugas di assignments/show.blade.php:
   - Menambahkan enctype="multipart/form-data" pada tag <form>.
   - Komponen input file modern TailwindCSS yang menerima .pdf, .doc, .docx, .zip.
   - Keterangan jelas: "Format berkas: PDF, DOCX, ZIP (Maksimal 10 MB)".
   - Jika mahasiswa sudah pernah submit ($assignment->submissions atau $userSubmission ada):
     Tampilkan box ringkasan pengumpulan:
     * Nama berkas asli ($submission->original_name)
     * Ukuran berkas ($submission->formatted_file_size ?? number_format($submission->file_size / 1024, 1) . ' KB')
     * Tanggal & jam submit
     * Badge status: "Tepat Waktu" (hijau) atau "Terlambat" (merah) berdasarkan is_late
     * Tombol aksi "Unduh Berkas" dengan link href="{{ route('submissions.download', $submission) }}"
2. Di halaman penilaian submissions/show.blade.php dan tabel submissions/index.blade.php:
   - Tampilkan tombol unduh file jawaban mahasiswa route('submissions.download', $submission) yang mudah dilihat dosen.
3. Desain harus konsisten dengan warna dan estetika KampusLMS (TailwindCSS modern, rounded-xl, shadow-sm).
```

---

## 4. Shabrina Putri Az-Zahra
- **NIM:** 10241066  
- **Peran:** *Database, Validasi & Testing (Form Request & Automated Security Tests)*  

### 📌 Tugas:
1. Membuat dedicated Form Request:
   - `StoreSubmissionRequest` (`app/Http/Requests/StoreSubmissionRequest.php`): Aturan validasi file pengumpulan tugas (`required`, `file`, `mimes:pdf,doc,docx,zip`, `max:10240`), note (`nullable`, `string`, `max:2000`), beserta pesan validasi bahasa Indonesia.
   - `StoreMaterialRequest` (`app/Http/Requests/StoreMaterialRequest.php`): Aturan validasi materi (`mimes:pdf,pptx,ppt,docx,doc`, `max:20480` jika tipe berkas, dan validasi URL jika tipe link).
2. Membuat pengujian otomatis keamanan berkas di `tests/Feature/FileUploadSecurityTest.php` untuk memvalidasi:
   - Mahasiswa tidak bisa mengunduh berkas submission mahasiswa lain (menghasilkan status HTTP 403 Forbidden).
   - Pengguna tanpa login ditolak saat mengakses URL unduhan berkas (HTTP 401 / 302).
   - Pengunggahan berkas skrip PHP yang di-rename menjadi `.pdf` ditolak oleh validasi `mimes` (*magic bytes check*).
   - Berkas fisik benar-benar terhapus dari storage saat data submission atau materi dihapus.

### 📁 File Repo yang Diberikan ke AI:
- `app/Http/Requests/StoreCourseRequest.php` (sebagai acuan contoh Form Request)
- `tests/Feature/AuthorizationWebTest.php` (sebagai acuan contoh Feature Test)
- `database/migrations/2026_09_14_092829_create_submissions_table.php`
- `app/Models/Submission.php`

### 💬 Prompt untuk AI Shabrina:
```markdown
Konteks: Saya mengerjakan validasi server-side dan automated test suite untuk modul Minggu 9 proyek KampusLMS (Laravel 12).
Tugas saya adalah memastikan validasi upload berkas kebal dari pemalsuan ekstensi dan membuat test suite keamanan berkas.

Berikut contoh Form Request dan file test yang ada di proyek kami:
[Tempel StoreCourseRequest.php dan potongan AuthorizationWebTest.php]

Tolong buatkan:
1. Form Request StoreSubmissionRequest (app/Http/Requests/StoreSubmissionRequest.php):
   - Rules:
     'file' => ['required', 'file', 'mimes:pdf,doc,docx,zip', 'max:10240'],
     'note' => ['nullable', 'string', 'max:2000'],
   - Pesan kustom bahasa Indonesia (misal: 'Format berkas harus PDF, DOC, DOCX, atau ZIP', 'Ukuran maksimal berkas adalah 10 MB').
2. Form Request StoreMaterialRequest (app/Http/Requests/StoreMaterialRequest.php):
   - Rules untuk upload materi dosen (file max 20 MB mimes pdf/pptx/ppt/docx/doc jika type == file, external_url url jika type == link).
3. Test suite keamanan tests/Feature/FileUploadSecurityTest.php:
   - test_student_cannot_download_other_student_submission_file_prevents_idor() -> assertStatus(403)
   - test_unauthenticated_user_cannot_download_submission_file() -> assertRedirect('/login')
   - test_file_with_fake_pdf_extension_is_rejected() -> gunakan UploadedFile::fake()->createWithContent('malicious.pdf', '<?php echo 123; ?>') dan assertSessionHasErrors('file')
   - test_deleting_submission_removes_physical_file_from_storage() -> gunakan Storage::fake('local') dan assert bahwa Storage::disk('local')->assertMissing(...)

Gunakan standar Laravel 12 dan PHP 8.4.
```

---

## 📋 Checklist Verifikasi Akhir Sebelum Demo Kelompok
- [ ] Berkas materi tersimpan di disk privat `storage/app/private/materials/...` (Bukan di `storage/app/public`).
- [ ] Berkas submission tugas tersimpan di `storage/app/private/submissions/...` dengan nama acak unik.
- [ ] Mahasiswa mencoba mengunduh submission teman via URL langsung dan ditolak dengan status **403 Forbidden**.
- [ ] Pengumpulan ulang oleh mahasiswa yang sama menimpa submission lama dan tidak memicu error duplicate key.
- [ ] Berkas fisik terhapus dari storage saat record materi atau tugas dihapus.
- [ ] Seluruh pengujian otomatis `php artisan test` berstatus hijau (PASS).
