## Nama: Raihandy Wijaya
## NIM: 10241064
## Peran: Fullstack & Backend Integrasi (Submission Upload Controller & API Integration)

# LAPORAN PRAKTIKUM MINGGU 9
### Pengelolaan Unggahan Berkas, Penyimpanan Privat, dan Data Dinamis

---

## 1. READ — Bedah Pengelolaan Berkas Privat vs Publik di Laravel 12

Pada Minggu 9, arsitektur KampusLMS ditingkatkan untuk mendukung penanganan berkas fisik (materi perkuliahan dan pengumpulan tugas) dengan memisahkan tegas antara berkas publik dan berkas privat:

| Aspek | Berkas Publik (`storage/app/public`) | Berkas Privat (`storage/app/private` / `local`) |
|---|---|---|
| **Contoh di KampusLMS** | Foto profil pengguna, logo instansi, favicon | Berkas materi kuliah, berkas jawaban/tugas mahasiswa |
| **Metode Akses** | Akses langsung peramban via symlink `public/storage/...` | Melalui Controller terlindungi + `Gate::authorize()` via Policy |
| **Pengecekan Otorisasi** | Tidak ada (siapa saja yang tahu URL dapat mengunduh) | Wajib lolos Policy (Dosen pengampu, Mahasiswa terdaftar, Admin) |
| **Keamanan Nama Berkas** | Sering memakai nama asli atau hash publik | Wajib nama acak di disk fisik, nama asli di kolom `original_name` |
| **Risiko Kebocoran** | Sangat tinggi jika dipakai untuk tugas/materi berbayar | Nol jika controller menerapkan otorisasi ketat |

---

## 2. BREAK — Analisis 8 Kerusakan Berkas & Keamanan (Modul 9.3)

| # | Skenario Kerusakan yang Diuji | Pengamatan Dampak & Risiko | Solusi & Pencegahan di KampusLMS |
|---|---|---|---|
| 1 | Memindahkan submission ke `storage/app/public` dan akses URL langsung | Tugas seluruh kelas dapat diunduh siapa saja dari internet tanpa login. | Simpan mutlak di disk `'local'` (privat) di bawah `storage/app/private/`. |
| 2 | Menghapus `Gate::authorize()` dari endpoint download submission | Mahasiswa A mengganti ID submission di URL dan dapat mengintip jawaban teman (*IDOR pada File Download*). | Wajib memanggil `Gate::authorize('view', $submission)` sebelum memproses unduhan. |
| 3 | Menyimpan berkas dengan `storeAs()` memakai nama asli pengguna | Dua mahasiswa mengunggah `tugas.pdf` ke folder tugas yang sama; berkas mahasiswa pertama tertimpa dan hilang. | Gunakan `$file->store(...)` dengan nama unik acak; simpan nama asli di database. |
| 4 | Validasi berkas hanya memakai `extensions:pdf` tanpa `mimes:pdf` | Penyerang mengunggah skrip PHP berbahaya yang diberi nama `shell.pdf` dan lolos validasi. | Wajib menggunakan aturan `mimes:pdf,...` yang memverifikasi *magic bytes* isi berkas. |
| 5 | Mengisi aturan validasi `max:5` dengan asumsi satuan Megabyte | Berkas ukuran 1 MB tertolak karena satuan aturan `max` di Laravel adalah Kilobyte (5 KB). | Gunakan konversi KB yang benar (`max:5120` = 5 MB, `max:10240` = 10 MB). |
| 6 | Nilai `upload_max_filesize` di `php.ini` bernilai 2M | Unggahan berkas 3 MB langsung ditolak web server sebelum request mencapai validasi Laravel. | Pastikan `upload_max_filesize` dan `post_max_size` pada PHP server lebih besar dari validasi aplikasi. |
| 7 | Menggunakan `Submission::create` alih-alih `updateOrCreate` | Mahasiswa yang mengumpulkan ulang tugas memicu error duplicate entry composite key. | Gunakan `updateOrCreate(['assignment_id' => ..., 'user_id' => ...])`. |
| 8 | Menghapus record tugas/submission langsung dari basis data | Baris database terhapus tetapi berkas fisik tertinggal menjadi berkas yatim (*orphan files*). | Pasang lifecycle hook `booted() deleting` pada model Eloquent dan hapus berkas fisik lama saat re-upload. |

---

## 3. FIX - w09

Pada latihan praktikum branch `w09`:
1. **Pemisahan Disk Storage:** Mengubah konfigurasi storage materi dan submission dari `public` ke `local` (privat).
2. **Penutupan IDOR Unduhan:** Menambahkan otorisasi `Gate::authorize('view', $submission)` dan `Gate::authorize('download', $material)` pada method unduh di controller.
3. **Penamaan Berkas Acak:** Mengganti metode penyimpanan yang memakai nama pengguna asli menjadi `$file->store(...)` acak di direktori `submissions/{assignment_id}`.
4. **Validasi Magic Bytes:** Mengganti aturan validasi dari pengecekan ekstensi dangkal menjadi `mimes:pdf,doc,docx,zip` dengan batasan `max:10240` (10 MB).
5. **Pembersihan Berkas Fisik:** Menambahkan pemanggilan `Storage::disk('local')->delete()` saat berkas ditimpa atau record dihapus agar tidak meninggalkan sampah di media penyimpanan.

---

## 4. BUILD — Kontribusi & Implementasi Raihandy Wijaya

Sebagai **Fullstack & Backend Integrasi (Submission Upload Controller & API Integration)**, saya bertanggung jawab penuh atas alur pengumpulan tugas mahasiswa, baik melalui antarmuka Web maupun RESTful API:

### 1. Pembaruan Method `submit` di `AssignmentController.php`
- **Otorisasi Server-Side:** Memanggil `Gate::authorize('submit', $assignment)` via `AssignmentPolicy` sebelum memproses data.
- **Validasi Deadline Server-Side:**
  - Menghitung keterlambatan secara server-side: `$isLate = now()->gt($assignment->due_at)`.
  - Jika terlambat dan `$assignment->allow_late == false`, sistem menolak pengumpulan dan mengembalikan respon `back()->with('error', 'Tenggat waktu pengumpulan telah berakhir.')`.
  - Jika tugas berstatus `closed` dan tidak mengizinkan keterlambatan, pengumpulan ditolak.
- **Validasi Berkas Ketat:**
  - Menegakkan aturan: `file` wajib (`required`), berkas valid (`file`), tipe MIME aman (`mimes:pdf,doc,docx,zip`), dan batas ukuran maksimal 10 MB (`max:10240`).
  - Pesan validasi berbahasa Indonesia yang jelas dan informatif.
- **Pembersihan Berkas Lama (Anti-Orphan Files):**
  - Sebelum berkas baru disimpan, sistem memeriksa apakah mahasiswa sudah pernah mengumpulkan tugas tersebut (`existingSubmission`).
  - Jika ada berkas lama di disk privat `'local'`, berkas lama dihapus via `Storage::disk('local')->delete(...)`.
- **Penyimpanan Acak di Disk Privat:**
  - Berkas fisik disimpan ke disk `'local'` di folder `submissions/{assignment_id}` dengan nama acak unik:
    `$path = $file->store("submissions/{$assignment->id}", 'local');`
- **Operasi Idempotent `updateOrCreate`:**
  - Menggunakan kunci komposit `['assignment_id' => $assignment->id, 'user_id' => $user->id]`.
  - Menyimpan metadata lengkap: `file_path`, `original_name`, `file_size`, `note`, `submitted_at`, dan `is_late`.

### 2. Pendaftaran Route Unduhan Submission di `routes/web.php`
- Route unduhan submission terdaftar dengan nama `submissions.download`:
  `GET /submissions/{submission}/download` -> `SubmissionController@download`
- Dilengkapi alias `/pengumpulan-tugas/{submission}/download` untuk kompatibilitas tautan bahasa Indonesia.
- Terproteksi oleh middleware `auth` dan otorisasi `Gate::authorize('view', $submission)` sehingga hanya pemilik berkas, dosen pengampu, dan admin yang dapat mengunduh.

### 3. Pembaruan Endpoint REST API di `app/Http/Controllers/Api/SubmissionController.php`
- Memperbarui method `store(Request $request, Assignment $assignment)`:
  - Validasi tipe MIME berkas: `mimes:pdf,doc,docx,zip` dan `max:10240`.
  - Penyimpanan berkas diarahkan ke disk privat `'local'` pada folder `submissions/{$assignment->id}`.
  - Mengembalikan representasi `SubmissionResource` dengan status HTTP 201 Created.

### 4. Pembuatan Test Suite Otomatis (`SubmissionUploadTest.php`)
Dibuat pengujian fitur lengkap di `tests/Feature/SubmissionUploadTest.php` yang mencakup 9 skenario:
1. `test_student_can_submit_assignment_with_valid_file_to_private_storage`: Memverifikasi berkas tersimpan aman di disk privat dan metadata tersimpan di database.
2. `test_resubmitting_assignment_deletes_old_file_and_updates_submission`: Memverifikasi berkas lama otomatis dihapus dari storage fisik saat pengumpulan ulang (anti-orphan).
3. `test_submission_past_deadline_rejected_if_allow_late_is_false`: Memverifikasi penolakan pengumpulan saat melewati tenggat dan late submission dimatikan.
4. `test_submission_past_deadline_accepted_as_late_if_allow_late_is_true`: Memverifikasi pengumpulan diterima dengan flag `is_late = true` saat late submission diaktifkan.
5. `test_submission_rejects_disallowed_file_extension`: Memverifikasi berkas non-dokumen (seperti `.exe`) ditolak.
6. `test_student_can_download_own_submission_file`: Memverifikasi mahasiswa dapat mengunduh berkas miliknya sendiri.
7. `test_dosen_can_download_student_submission_file`: Memverifikasi dosen pengampu dapat mengunduh berkas mahasiswa.
8. `test_other_student_cannot_download_submission_prevents_idor`: Memverifikasi upaya IDOR mahasiswa lain mengunduh berkas teman ditolak (403 Forbidden).
9. `test_unauthenticated_user_cannot_download_submission`: Memverifikasi pengguna tanpa sesi login diarahkan ke halaman login.

Hasil pengujian otomatis: **66 passed (193 assertions), 0 failures**.
