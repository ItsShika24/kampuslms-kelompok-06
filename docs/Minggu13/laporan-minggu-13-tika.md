# Laporan Praktikum Minggu 13 — Kolaborasi, Version Control, dan Peer Review
## Nama: Tika Mila Wahyuni
## NIM: 10241070
## Kelompok: 06 (EduKampus)
**Peran Utama:** Backend & Security Lead  
**Repositori:** KampusLMS (Laravel 12)  
**Dokumen Pendukung:** [`.github/pull_request_template.md`](../../.github/pull_request_template.md)  

---

## 1. Tahap 1: READ — Analisis Riwayat Git Pribadi & Kelompok (30 Menit)

Sesuai instruksi Modul 13 Subbab 13.3, dilakukan inspeksi riwayat Git lokal repositori `kampuslms-kelompok-06` menggunakan perintah:
```bash
git shortlog -sn
git log --author="Tika" --oneline
git log --author="Tika" --stat
```

Berikut adalah jawaban refleksi mandiri atas 5 pertanyaan evaluasi riwayat Git:

### 1.1 Jumlah Commit Pribadi vs Anggota Tim
Berdasarkan log repositori resmi per Minggu 13:
* **Tika Mila Wahyuni:** 101 commit (~61% total commit tim)
* **Shabrina Putri Azzahra:** 41 commit (gabungan akun `@ItsShika24` [27 commit] dan `@shabrinakeif` [14 commit])
* **Raihandy Wijaya:** 16 commit
* **Oktavia Nur Rahmadani:** 9 commit (gabungan akun `@06Victoire` [8 commit] dan Nama Lengkap [1 commit])
* **Total Commit Proyek:** ~167 commit

**Analisis Reflektif:**
Porsi commit saya paling dominan di dalam tim karena memegang tanggung jawab arsitektur dasar backend, pengamanan otorisasi (*5 Laravel Policies*), mitigasi IDOR, pembersihan kueri N+1 global, integrasi antrean latar belakang (*Supervisor Worker Daemon*), konfigurasi Dockerfile produksi, serta penggabungan (*merge & conflict resolution*) dari Pull Request rekan-rekan tim. Meskipun demikian, jumlah commit bukanlah indikator tunggal kontribusi, karena rekan-rekan memegang modul dengan lingkup berbeda seperti API Sanctum (Raihandy), UI Blade & Form Validation (Oktavia), dan Skema Database & Testing (Shabrina).

### 1.2 Konsistensi Commit Mingguan
* **Temuan:** Dari Minggu 1 (setup awal) hingga Minggu 12 (deployment produksi), **tidak ada minggu yang kosong** tanpa commit atas nama saya.
* **Distribusi:** Kontribusi berlangsung konsisten di setiap tahapan milestone mingguan, dengan lonjakan aktivitas commit pada Minggu 4 (CRUD & Form Request), Minggu 7 (Autentikasi & Otorisasi), Minggu 10 (Event/Queue Worker), Minggu 11 (Optimasi N+1 & Cache), dan Minggu 12 (Docker, Nginx, & Supervisor).

### 1.3 Kepatuhan terhadap *Conventional Commits*
* **Evaluasi:**
  * **Minggu 1 – Minggu 3:** Pesan commit belum sepenuhnya disiplin mengikuti standar *Conventional Commits* (masih menggunakan pesan seperti `catatan-minggu1`, `Update minggu 01`, `Build 1`, `Update Minggu2 dan code`).
  * **Minggu 4 – Minggu 13:** Pesan commit sudah 100% disiplin mengikuti konvensi baku industri (`feat:`, `fix:`, `docs:`, `refactor:`, `style:`, `chore:`).
  * **Persentase Kepatuhan Total:** Sekitar **82%** dari seluruh riwayat commit saya telah mematuhi standar *Conventional Commits*.

### 1.4 Temuan Pesan Commit Buruk & Penulisan Ulang yang Benar
* **Commit Buruk yang Ditemukan:**  
  `Commit 081ebbd — "Build 1"` dan `Commit d2ddb37 — "Update Minggu2 dan code"`
* **Kelemahan Pesan Tersebut:**  
  Pesan tersebut sangat ambigu, tidak menyebutkan modul yang dimodifikasi, dan tidak menjelaskan alasan perubahan. Penguji atau rekan tim tidak dapat mengetahui apa yang sebenarnya dikerjakan tanpa membaca diff baris per baris.
* **Penulisan Ulang yang Benar:**
  ```text
  feat(courses): implementasi skema migrasi tabel courses, model, dan seeder data dummy awal
  ```

### 1.5 Temuan Commit Terbesar (`git log --stat`) & Analisis Pemecahan
* **Commit Terbesar yang Ditemukan:**  
  `Commit 429cdda — feat(minggu-04): implementasi Form Request, PRG pattern, filter & pagination, flash message, dan dokumen praktikum`
* **Statistik Diff:** **14 files changed, 881 insertions(+), 199 deletions(-)** (Total 1.080 baris delta).
* **Analisis Kritis:**  
  Commit ini masuk dalam kategori *giant commit* yang terlalu luas karena mencakup 4 domain sekaligus: lapisan validasi Form Request, controller logic, Blade view antarmuka pengguna, dan dokumentasi markdown praktikum.
* **Rekomendasi Pemecahan yang Seharusnya Dilakukan:**
  Commit raksasa tersebut idealnya dipecah menjadi minimal 3 commit terpisah yang berumur pendek:
  1. `feat(validation): tambah StoreCourseRequest, UpdateCourseRequest, dan sanitasi input pengguna`
  2. `feat(controllers): terapkan PRG pattern, query filtering, dan pagination pada CourseController dan UserController`
  3. `docs(m04): tambahkan laporan praktikum minggu 4 dan dokumentasi pengujian form`

---

## 2. Tahap 2: Penguatan Alur Kolaborasi Repositori Kelompok

Untuk memenuhi persyaratan Modul 13 Subbab 13.3 (BUILD Poin 9 & 10):

### 2.1 Pemasangan Pull Request Template
Telah dibuat berkas standar [`.github/pull_request_template.md`](../../.github/pull_request_template.md) yang mewajibkan setiap pembuat PR menyertakan:
1. Deskripsi apa yang diubah dan rasionalisasinya (*Apa & Kenapa*).
2. Langkah reproduksi/pengujian fitur (*Cara Menguji*).
3. Daftar periksa kepatuhan arsitektur (*Server-side validation, Gate authorization, pencegahan N+1, feature test, pembersihan debug `dd()`*, dan proteksi `.env`).
4. Bagian transparansi AI (*sebutkan bagian yang dibantu AI dan apa yang dimodifikasi*).

### 2.2 Penegakan *Branch Protection* di GitHub
Pengaturan proteksi branch pada repositori `ItsShika24/kampuslms-kelompok-06` telah dikonfigurasi:
* **Target Branch:** `main` dan `dev`
* **Aturan Proteksi yang Diberlakukan:**
  1. **Require a pull request before merging:** Melarang push langsung (*direct push*) ke branch utama.
  2. **Require approvals:** Wajib minimal 1 persetujuan review dari anggota tim sebelum merge diizinkan.
  3. **Require status checks to pass before merging:** Memastikan seluruh pipeline pengujian otomatis (CI GitHub Actions) berstatus hijau.
  4. **Do not allow bypassing the above settings:** Pengaturan berlaku untuk semua kontributor termasuk admin repositori.

---

## 3. Tahap 3: Eksekusi Peer Review Repositori Kelompok Lain (BUILD — Bobot Nilai 10%)

Sebagai pemegang peran **Backend & Security Lead**, saya melakukan peninjauan kode (*peer review*) secara independen terhadap repositori rekan kelompok sasaran (**Kelompok 07**) pada Pull Request utama yang dikerjakan oleh **Vanessa Marie Tandiarru** (`Vexusian`):

* **Tautan PR Sasaran:** [PR #58: `feat: tambah fitur impor dan ekspor data mahasiswa serta nilai (CSV)`](https://github.com/Vexusian/kampuslms-B-kelompok-07/pull/58)
* **Akun Reviewer:** `@tikaacode` (Tika Mila Wahyuni)
* **Fokus Peninjauan:** **Keamanan (Security)**, **Performa Kueri Database**, dan **Arsitektur/Struktur Kode**.

Berikut adalah 3 temuan hasil peninjauan kode resmi yang telah saya kirimkan di kolom komentar PR tersebut:

---

### 🔍 Temuan 1: Keamanan — Risiko Overwrite dan Perubahan Role Akun Eksisting (Privilege Demotion)
* **Lokasi Berkas & Baris:** `app/Http/Controllers/DataExchangeController.php:140-154`
* **Deskripsi Masalah:**  
  Pada method `importStudents()`, pembaruan akun mahasiswa yang sudah ada di database ditangani dengan kode:
  ```php
  $user = User::withTrashed()->where('email', $email)->first();
  if ($user) {
      if ($user->trashed()) {
          $user->restore();
      }
      $user->name = $name;
      if ($nim) {
          $user->nim_nip = $nim;
      }
      if ($password !== '') {
          $user->password = Hash::make($password);
      }
      $user->role = 'mahasiswa';
      $user->save();
  }
  ```
* **Risiko Teknis:**  
  Jika berkas CSV yang diunggah secara tidak sengaja (atau sengaja) memuat alamat email yang sudah terdaftar sebagai **Admin** atau **Dosen**, baris `$user->role = 'mahasiswa'` akan langsung menurunkan derajat hak akses (*role demotion*) akun tersebut menjadi mahasiswa biasa dan menimpa kata sandinya. Hal ini berisiko memicu *Denial of Service* atau penguncian akun pengelola sistem.
* **Saran Perbaikan Konkret:**  
  Tambahkan pengecekan role sebelum memodifikasi user. Jika akun yang ditemukan memiliki role selain `mahasiswa`, lewati baris tersebut dan catat sebagai peringatan/error:
  ```php
  if ($user) {
      if ($user->role !== 'mahasiswa') {
          $errorCount++;
          $errors[] = "Baris #{$totalRows}: Email {$email} terdaftar sebagai {$user->role} dan tidak dapat diubah via impor mahasiswa.";
          continue;
      }
      // Lanjutkan pembaruan khusus data mahasiswa...
  }
  ```

---

### 🔍 Temuan 2: Performa — Masalah N+1 Query Loop pada Proses Ekspor Nilai
* **Lokasi Berkas & Baris:** `app/Http/Controllers/DataExchangeController.php:195-207`
* **Deskripsi Masalah:**  
  Pada method `exportGrades()`, pemanggilan eager loading di awal tertimpa oleh kueri baru di dalam perulangan:
  ```php
  $course->loadMissing(['assignments.submissions.grade', 'students']);
  $assignments = $course->assignments()->orderBy('id')->get();
  $students = $course->students()->orderBy('name')->get();
  ...
  foreach ($assignments as $assignment) {
      $submissions = $assignment->submissions()->with('grade')->get()->keyBy('user_id');
      ...
  }
  ```
* **Risiko Teknis:**  
  Pemanggilan method query `$assignment->submissions()` (dengan tanda kurung) di baris 206 mengeksekusi kueri SQL baru ke database pada setiap iterasi tugas. Jika suatu mata kuliah memiliki 20 tugas, maka akan dieksekusi 1 kueri assignments + 20 kueri submissions bertingkat secara berulang (*N+1 Query Issue*). Pada kelas berkapasitas besar, hal ini memicu lonjakan beban CPU database dan meningkatkan latensi ekspor berkas.
* **Saran Perbaikan Konkret:**  
  Lakukan eager loading langsung pada kueri `$assignments` dan manfaatkan relasi *in-memory* (tanpa tanda kurung method):
  ```php
  $assignments = $course->assignments()
      ->with(['submissions.grade'])
      ->orderBy('id')
      ->get();
  $students = $course->students()->orderBy('name')->get();

  return response()->streamDownload(function () use ($assignments, $students) {
      ...
      foreach ($assignments as $assignment) {
          // Ambil dari koleksi memory, tidak memicu kueri SQL baru
          $submissions = $assignment->submissions->keyBy('user_id');

          foreach ($students as $student) {
              $submission = $submissions->get($student->id);
              ...
          }
      }
  ...
  ```

---

### 🔍 Temuan 3: Struktur & Keterbacaan — Penegakan Otorisasi Berbasis Policy dan Perapian Controller
* **Lokasi Berkas & Baris:** `app/Http/Controllers/DataExchangeController.php:271-274`
* **Deskripsi Masalah:**  
  Pada method `importGrades()`, otorisasi dilakukan secara manual (*hardcoded*) di dalam controller:
  ```php
  $user = auth()->user();
  if ($user->role !== 'dosen' || $course->lecturer_id !== $user->id) {
      abort(403, 'Hanya dosen pengampu mata kuliah ini yang berhak mengimpor nilai.');
  }
  ```
* **Risiko Teknis:**  
  1. Melanggar prinsip *Single Source of Truth* untuk otorisasi otentikasi Laravel. Jika kelak ada perubahan aturan (misalnya Admin atau Asisten Dosen diperbolehkan mengimpor nilai di `CoursePolicy`), controller ini akan *out-of-sync*.
  2. Method `importGrades()` menjadi *Fat Controller* (mencapai 146 baris) karena menggabungkan validasi, parsing streaming CSV, pencarian mahasiswa, mutasi multi-tabel (`Submission` & `Grade`), dan pencatatan log dalam satu tempat.
* **Saran Perbaikan Konkret:**  
  1. Gunakan otorisasi standar Laravel Policy:
     ```php
     Gate::authorize('update', $course);
     ```
  2. Pertimbangkan untuk memisahkan logika parsing baris CSV ke dalam *Action Class* atau *Service Class* terpisah (misal `App\Actions\ImportCourseGradesAction`) agar controller tetap ringkas dan logika impor dapat diuji secara terisolasi melalui unit testing.

---

## 4. Checkpoint Evaluasi Mandiri Minggu 13

* [x] **Jumlah Commit:** 101 commit atas nama Tika Mila Wahyuni (tercatat di `git shortlog -sn`).
* [x] **Pelajaran Kebocoran Secret:** Paham penuh bahwa `git rm --cached .env` hanya menghapus file dari commit terbaru (*HEAD*), tetapi riwayat nilai rahasia tetap tersimpan di riwayat commit lama Git. Mitigasi wajibnya adalah rotasi kredensial produksi dan pembersihan riwayat via `git-filter-repo`.
* [x] **Pentingnya PR Kecil:** PR kecil (1 fitur = 1 PR) membuat review kode efektif, mengurangi risiko merge conflict, dan mempermudah pelacakan regresi bug.
* [x] **Keterbatasan Evaluasi Commit:** Memahami bahwa `git shortlog` dapat dimanipulasi dengan opsi `--author`, sehingga integritas kontribusi dinilai melalui pengujian riil, wawancara checkpoint, dan kemampuan menjelaskan logika kode.
