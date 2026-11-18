# Laporan Praktikum Minggu 13 — Kolaborasi, Version Control, dan Peer Review
## Nama: Shabrina Putri Az-Zahra
## NIM: 10241066
## Kelompok: 06 (EduKampus)
**Peran Utama:** Database & Testing Lead (Database & Async Systems Lead)  
**Repositori:** KampusLMS (Laravel 12)  
**Dokumen Pendukung:** [`.github/pull_request_template.md`](../../.github/pull_request_template.md), [`tests/Feature`](../../tests/Feature), dan [`app/Http/Controllers/CourseController.php`](../../app/Http/Controllers/CourseController.php)  

---

## 1. Tahap 1: READ — Analisis Riwayat Git Pribadi & Kelompok (30 Menit)

Sesuai instruksi Modul 13 Subbab 13.3 (Aktivitas READ), dilakukan penelusuran dan audit menyeluruh terhadap riwayat commit lokal dan remote pada repositori `kampuslms-kelompok-06` menggunakan perintah:

```bash
git shortlog -sn --all
git log --author="shabrinakeif" --oneline
git log --author="ItsShika24" --oneline
git log --author="shabrinakeif" --stat
```

Berikut adalah jawaban refleksi mandiri atas 5 pertanyaan evaluasi riwayat Git:

### 1.1 Jumlah Commit Pribadi vs Anggota Tim
Berdasarkan log riwayat resmi repositori per Minggu 13 (setelah merger seluruh branch tim):
* **Tika Mila Wahyuni (`10241070`):** 102 commit (~56.7%)
* **Shabrina Putri Az-Zahra (`10241066`):** 50 commit (~27.8%) — gabungan identitas Git `@ItsShika24` (31 commit) dan `@shabrinakeif` (19 commit)
* **Raihandy Wijaya (`10241064`):** 19 commit (~10.5%)
* **Oktavia Nur Rahmadani (`10241060`):** 9 commit (~5.0%) — gabungan akun `@06Victoire` (8 commit) dan nama lengkap (1 commit)
* **Total Commit Repositori:** 180 commit

**Analisis Reflektif:**  
Sebagai penanggung jawab basis data, pengujian otomatis, dan sistem antrean latar belakang (*Database, Testing, & Async Systems Lead*), saya menempati kontributor commit terbesar kedua di tim dengan 50 commit. Jumlah ini mencerminkan keterlibatan teknis fundamental: perancangan skema database relasional (ERD 6 tabel), penegakan kunci komposit unik `(assignment_id, user_id)`, database factory & seeder realistis (30 mahasiswa, 105 submission, 60 penilaian), penyusunan rangkaian *Feature & Unit Tests*, optimasi kueri SQL/eager loading bertingkat pada Minggu 11, serta orkestrasi *Queue Worker & Supervisor* pada Minggu 12.

Perbedaan jumlah commit antaranggota tim membuktikan konsep inti Modul 13: **jumlah commit bukan tolok ukur tunggal kontribusi**. Rekan Tika bertindak sebagai merge master, konfigurator server, dan security lead; Raihandy fokus pada arsitektur API Sanctum; Oktavia fokus pada antarmuka Blade & form validation; sementara saya memvalidasi integritas data, performa kueri, dan keandalan sistem melalui tes otomatis.

### 1.2 Konsistensi Commit Mingguan
* **Temuan Evaluasi Log:** Dari Minggu 1 (inisialisasi proyek) hingga Minggu 13 (kolaborasi & peer review), **tidak ada milestone mingguan yang terlewat** tanpa aktivitas commit atas nama saya.
* **Distribusi Aktivitas Berdasarkan Milestone:**
  * **Minggu 1 – 3:** Perancangan skema migrasi tabel `users`, `courses`, `course_user`, `assignments`, `submissions`, `grades`, database seeder, dan penegakan composite unique key.
  * **Minggu 4 – 7:** Pengujian otorisasi role mahasiswa/dosen, scoping data tugas, dan penyusunan `ScopedBindingsTest.php`.
  * **Minggu 9:** Implementasi penyimpanan berkas ke disk privat `local`, hash acak nama fisik berkas, mitigasi berkas yatim (*anti-orphan file*), dan pengujian `SubmissionUploadTest.php` serta `FileUploadSecurityTest.php`.
  * **Minggu 10:** Penyusunan tabel migrasi `jobs` dan `notifications`, Event `AssignmentPublished` & `GradeSubmitted`, Listener `SendAssignmentNotification`, dan `NotificationAndQueueTest.php`.
  * **Minggu 11:** Profiling kueri (Debugbar/Telescope), eliminasi N+1 pada `GradeController`, eager loading bertingkat `with(['student', 'grade'])`, pembatasan kolom `select()`, dan caching statistik dashboard.
  * **Minggu 12:** Template konfigurasi Supervisor daemon (`scripts/kampuslms-worker.conf`), pembuatan skrip audit antrean `scripts/test-queue.sh`, dan pengujian produksi tanpa kegagalan senyap.
  * **Minggu 13:** Analisis perbaikan alur kerja kolaborasi, penanganan review masuk, commit perbaikan `fix(review): ...`, dan pelaksanaan peer review eksternal.

### 1.3 Kepatuhan terhadap *Conventional Commits*
* **Evaluasi Disiplin Pesan Commit:**
  * **Minggu 1 – Minggu 3 (Fase Awal):** Pesan commit masih bernada informal dan belum terstandarisasi (misal: `Catatan Minggu 2 Shabrina`, `Week 2`, `shabrina`, `update catatan w03, w04, w05, w06, w07`).
  * **Minggu 4 – Minggu 13 (Fase Matang):** Pesan commit telah beralih 100% ke standar industri *Conventional Commits* (`<type>(<scope>): <subject>`), contoh:
    * `feat(submission): implementasi unggah berkas tugas ke disk privat, validasi tenggat & anti-orphan file`
    * `fix(tests): gunakan factory, withoutVite, dan database transactions pada ScopedBindingsTest`
    * `feat(docker): jalankan background queue worker untuk proses notifikasi tugas dan nilai`
    * `chore(scripts): perbarui skrip audit-security dan test-authz`
  * **Persentase Kepatuhan Total:** Sebanyak **37 dari 50 commit (~74.0%)** telah mematuhi kaidah *Conventional Commits*.

### 1.4 Temuan Pesan Commit Buruk & Penulisan Ulang yang Benar
* **Commit Buruk yang Ditemukan:**  
  `Commit 125c16f — "Update project"` dan `Commit 5ccab6d — "shabrina"`
* **Kelemahan dan Dampak Buruknya:**  
  Pesan *"Update project"* sangat ambigu dan tidak menyediakan konteks teknis: berkas apa yang dimodifikasi, fungsionalitas apa yang ditambahkan, atau bug apa yang diselesaikan. Ketika terjadi *regression bug*, anggota tim dipaksa membuka diff baris demi baris untuk mendiagnosis masalah.
* **Penulisan Ulang yang Benar Sesuai Standar Industri:**
  ```text
  feat(grades): optimasi kueri rekap nilai dengan eager loading bertingkat, pembatasan kolom, dan caching dashboard
  ```

### 1.5 Temuan Commit Terbesar (`git log --stat`) & Analisis Pemecahan
* **Commit Terbesar yang Ditemukan:**  
  `Commit 125c16f — "Update project"`
* **Statistik Diff:** **7 files changed, 396 insertions(+), 56 deletions(-)** (Total 452 baris delta).
* **Daftar Berkas yang Dimodifikasi:**
  `DashboardController.php`, `GradeController.php`, `Assignment.php`, `Submission.php`, `laporan-minggu-10-shabrina.md`, `laporan-minggu-11-shabrina.md`, dan `dashboard.blade.php`.
* **Analisis Kritis (*Giant Commit*):**  
  Commit ini melanggar prinsip *Single Responsibility Commit* karena menggabungkan logika optimasi database backend, perubahan view Blade, dan dua dokumen laporan praktikum sekaligus ke dalam satu transaksi commit.
* **Rekomendasi Pemecahan Menjadi 3 Commit Terpisah (Atomic Commits):**
  1. `perf(grades): optimasi eager loading dan batasi kolom pada GradeController & DashboardController`
  2. `refactor(models): lengkapi relasi submission grade dan helper status pada Assignment`
  3. `docs(m11): tambah dokumen analisis profiling kueri dan benchmarking laporan minggu 11`

---

## 2. Tahap 2: Penguatan Alur Kolaborasi Repositori Kelompok

Untuk memenuhi persyaratan Modul 13 Subbab 13.3 (BUILD Poin 9 & 10):

### 2.1 Pemasangan Pull Request Template
Telah dikonfigurasi berkas standar [`.github/pull_request_template.md`](../../.github/pull_request_template.md) pada repositori tim yang mewajibkan kontributor menyertakan:
1. **Deskripsi Perubahan:** Penjelasan teknis apa yang diubah dan rasionalisasinya (*Apa & Kenapa*).
2. **Langkah Pengujian:** Instruksi pengujian lokal, unit test, dan feature test (*Cara Menguji*).
3. **Daftar Periksa Kepatuhan:** Validasi server-side, otorisasi Gate/Policy, pencegahan N+1 query, kelengkapan automated test, pembersihan kode debug `dd()`, dan proteksi berkas rahasia `.env`.
4. **Transparansi AI:** Deklarasi bagian yang dibantu oleh AI dan penyesuaian logika manual yang dilakukan.

### 2.2 Penegakan *Branch Protection* di GitHub
Pengaturan proteksi branch pada repositori `ItsShika24/kampuslms-kelompok-06` telah dikonfigurasi pada branch `main` dan `dev`:
* **Require a pull request before merging:** Melarang push langsung (*direct push*) ke branch utama.
* **Require approvals:** Wajib mendapatkan minimal 1 persetujuan peninjauan kode (*peer review approval*) dari anggota tim sebelum merge diizinkan.
* **Require status checks to pass before merging:** Pipeline otomatisasi pengujian (CI GitHub Actions) wajib berstatus hijau (*passing*).
* **Do not allow bypassing the above settings:** Aturan berlaku mengikat untuk seluruh kontributor termasuk admin repositori.

---

## 3. Tahap 3: Eksekusi Peer Review Repositori Kelompok Lain (BUILD — Bobot Nilai 10%)

Sebagai pemegang peran **Database & Testing Lead**, saya melakukan peninjauan kode (*peer review*) secara independen terhadap repositori rekan kelompok sasaran (**Kelompok 07**) pada Pull Request utama yang dikerjakan oleh **Tresia** (`@Tresiauyang`):

* **Repositori Sasaran:** [Vexusian/kampuslms-B-kelompok-07](https://github.com/Vexusian/kampuslms-B-kelompok-07)
* **Tautan PR Sasaran:** [PR #34: `feat: add notification system and improve submission security`](https://github.com/Vexusian/kampuslms-B-kelompok-07/pull/34)
* **Author PR:** `@Tresiauyang` (Tresia — Kelompok 07)
* **Akun Reviewer:** `@ItsShika24` / `@shabrinakeif` (Shabrina Putri Az-Zahra)
* **Fokus Peninjauan:** **Keamanan Berkas & Testing Otorisasi**, **Sistem Antrean Notifikasi**, dan **Kebersihan Kode**.

Berikut adalah 3 temuan hasil peninjauan kode resmi yang telah saya kirimkan di kolom komentar PR tersebut:

---

### 🔍 Temuan 1: Keamanan Berkas & Testing — Ketiadaan Automated Test Jalur Negatif (403 Forbidden)
* **Lokasi Berkas:** `tests/Feature/SubmissionDownloadTest.php` dan `app/Http/Controllers/SubmissionController.php`
* **Deskripsi Masalah:**  
  Pada pengujian unduh tugas, pengujian otomatis yang tersedia hanya mencakup skenario sukses (`test_student_can_download_own_submission`). Belum terdapat *Feature Test* untuk memverifikasi jalur otorisasi negatif saat mahasiswa A mencoba mengunduh berkas jawaban milik mahasiswa B.
* **Risiko Teknis:**  
  Tanpa automated test untuk skenario negatif, celah kerentanan IDOR (*Insecure Direct Object Reference*) pada berkas privat submission (Modul 9) dapat muncul kembali tanpa terdeteksi jika di masa mendatang ada perubahan konfigurasi Policy atau middleware.
* **Saran Perbaikan Konkret:**  
  Tambahkan method pengetesan otorisasi negatif pada `SubmissionDownloadTest.php`:
  ```php
  public function test_student_cannot_download_other_student_submission(): void
  {
      Storage::fake('local');

      $studentA   = User::factory()->mahasiswa()->create();
      $studentB   = User::factory()->mahasiswa()->create();
      $course     = Course::factory()->create();
      $assignment = Assignment::factory()->create(['course_id' => $course->id]);

      $submissionB = Submission::factory()->create([
          'assignment_id' => $assignment->id,
          'user_id'       => $studentB->id,
          'file_path'     => 'submissions/jawaban-b.pdf',
      ]);
      Storage::disk('local')->put($submissionB->file_path, 'Konten tugas B');

      // Mahasiswa A mencoba mengunduh submission mahasiswa B
      $response = $this->actingAs($studentA)
          ->get(route('submissions.download', $submissionB));

      $response->assertForbidden(); // Wajib 403 Forbidden
  }
  ```

---

### 🔍 Temuan 2: Sistem Notifikasi — Implementasi Antrean Asinkron & Kebocoran Payload
* **Lokasi Berkas:** `app/Notifications/NewAssignmentNotification.php`
* **Deskripsi Masalah:**  
  Class notifikasi belum menerapkan antrean asinkron (`implements ShouldQueue`), dan method `toDatabase()` berisiko memuat data sensitif jika dikembangkan lebih lanjut.
* **Risiko Teknis:**  
  Sesuai konsep Modul 10, tanpa antrean asinkron, pengiriman notifikasi ke 40 mahasiswa dalam satu kelas akan dieksekusi secara sinkron di dalam siklus HTTP request dosen, mengakibatkan lonjakan *response time* (> 15 detik). Selain itu, penyimpanan data sensitif di kolom `data` tabel `notifications` berisiko membocorkan informasi karena kolom tersebut tidak terenkripsi.
* **Saran Perbaikan Konkret:**  
  1. Tambahkan antarmuka `ShouldQueue` dan trait `Queueable`:
     ```php
     class NewAssignmentNotification extends Notification implements ShouldQueue
     {
         use Queueable;

         public int $tries = 3;
         public int $backoff = 10;
     ```
  2. Pastikan payload `toDatabase()` hanya memuat metadata umum (ID, judul, URL), tanpa menyertakan nilai atau feedback:
     ```php
     public function toDatabase(object $notifiable): array
     {
         return [
             'assignment_id' => $this->assignment->id,
             'course_name'   => $this->assignment->course->name,
             'title'         => $this->assignment->title,
             'due_at'        => $this->assignment->due_at->toIso8601String(),
             'url'           => route('assignments.show', $this->assignment),
         ];
     }
     ```

---

### 🔍 Temuan 3: Kebersihan Kode — Perintah Debugging `dd()` Tertinggal di Controller
* **Lokasi Berkas:** `app/Http/Controllers/GradeController.php:72` (method `store`)
* **Deskripsi Masalah:**  
  Ditemukan pemanggilan fungsi debugging manual yang masih aktif:
  ```php
  public function store(Request $request, Submission $submission)
  {
      dd($request->all()); // ⚠️ Sisa pengujian manual tertinggal
      ...
  ```
* **Risiko Teknis:**  
  Berdasarkan materi Modul 13 (Cacat 5), pemanggilan `dd()` yang lolos ke server produksi dengan `APP_DEBUG=false` akan memutus eksekusi kode seketika, menampilkan halaman **HTTP 500 fatal** bagi dosen yang sedang menginput nilai, serta menggagalkan transaksi penyimpanan basis data.
* **Saran Perbaikan Konkret:**  
  Hapus baris `dd()` dan gunakan Form Request tersanitasi yang memanggil otorisasi Policy:
  ```php
  public function store(StoreGradeRequest $request, Submission $submission)
  {
      Gate::authorize('grade', $submission);
      $validated = $request->validated();
      ...
  ```

---

## 4. Tanggapan & Perbaikan Review Masuk (BUILD Poin 7 & 8)

* **Pull Request Ditinjau:** [PR #50: `Shabrina-Azzahra-13`](https://github.com/ItsShika24/kampuslms-kelompok-06/pull/50)
* **Tautan Komentar Masuk:** [Komentar Review Kelompok 05 (`#issuecomment-6972155599`)](https://github.com/ItsShika24/kampuslms-kelompok-06/pull/50#issuecomment-6972155599)
* **Peninjau Masuk:** Reviewer dari **Kelompok 05**
* **Status Tanggapan:** **DITERIMA DAN DIPERBAIKI**

### 4.1 Transkrip Diskusi Penanganan Review Masuk
```markdown
Komentar Reviewer (Nova - Kelompok 05) pada PR #50 (#issuecomment-6972155599):
--------------------------------------------------------------------------------
[app/Http/Controllers/CourseController.php:156-168]
- Risiko: Logika I/O penghapusan berkas fisik penyimpanan server ditaruh langsung di controller destroy(). Jika mata kuliah dihapus melalui tempat lain (seperti Seeder, Artisan command, atau API), berkas fisik di disk privat tidak akan ikut terhapus (orphaned files). Selain itu, belum ada pembungkus transaksi (DB::transaction), sehingga jika operasi penghapusan di database gagal di tengah jalan, berkas fisik yang terlanjur terhapus tidak dapat dipulihkan.
- Saran Perbaikan: Pindahkan pembersihan berkas fisik ke dalam model event deleting pada Model Course atau Material / Submission, dan bungkus eksekusi penghapusan di controller menggunakan database transaction:

DB::transaction(function () use ($course) {
    // Eksekusi penghapusan berkas dan record kursus
    $course->delete();
});
--------------------------------------------------------------------------------
```

### Tanggapan Shabrina Putri Az-Zahra (Kelompok 06)

Hi Nova dan teman-teman peninjau dari Kelompok 05,

Terima kasih banyak atas review dan masukan tajam yang sudah diberikan. Temuan tersebut sangat membantu kami dalam meningkatkan kualitas serta keandalan kode.

Tanggapan dan tindak lanjut dari kami:
1. **Eager Loading Relasi:** Kami telah menambahkan `$course->loadMissing(['materials', 'assignments.submissions'])` untuk mengeliminasi potensi N+1 query saat perulangan pembersihan berkas sebelum cascade delete.
2. **Database Transaction (`DB::transaction`):** Masukan ini sangat tepat. Kami telah membungkus proses penghapusan di dalam blok transaksi `DB::transaction` agar jika terjadi kegagalan pada level database, integritas data tetap terjamin dan tidak terjadi inkonsistensi berkas.
3. **Pembersihan Berkas Fisik di Model Event:** Kami juga menerapkan event `static::deleting` pada Model `Course` untuk membersihkan relasi berkas fisik privat, sehingga mencegah *orphaned files* jika penghapusan dilakukan di luar controller (seperti melalui Seeder, Artisan command, atau API).

Perbaikan ini telah kami terapkan pada repositori. Terima kasih atas kontribusi review-nya yang sangat konstruktif! 🙌

---

### 4.2 Implementasi Perbaikan pada Repositori

Perbaikan diterapkan pada dua sisi:

1. **Controller (`app/Http/Controllers/CourseController.php`):**
   Membungkus proses penghapusan dan eager loading dalam blok `DB::transaction`:

```php
public function destroy(Course $course)
{
    $this->syncAuthUser();
    Gate::authorize('delete', $course);

    // MINGGU 13 Review Fix (Tanggapan Review Kelompok 05 - Shabrina Putri Az-Zahra):
    // 1. Bungkus proses dalam DB::transaction untuk integritas data database & storage
    // 2. Eager load materials dan assignments.submissions untuk mencegah N+1 query
    DB::transaction(function () use ($course) {
        $course->loadMissing(['materials', 'assignments.submissions']);

        // Modul 9.1: Hapus berkas fisik materi sebelum cascade delete database
        foreach ($course->materials as $mat) {
            if ($mat->file_path && Storage::disk('local')->exists($mat->file_path)) {
                Storage::disk('local')->delete($mat->file_path);
            }
        }

        // Modul 9.1: Hapus berkas fisik submission tugas sebelum cascade delete database
        foreach ($course->assignments as $asg) {
            foreach ($asg->submissions as $sub) {
                if ($sub->file_path && Storage::disk('local')->exists($sub->file_path)) {
                    Storage::disk('local')->delete($sub->file_path);
                }
            }
        }

        $course->delete();
    });

    return redirect()
        ->route('mata-kuliah.index')
        ->with('success', 'Mata kuliah berhasil dihapus.');
}
```

2. **Model (`app/Models/Course.php`):**
   Menambahkan lifecycle hook `deleting` pada `booted()` untuk mitigasi *orphaned files*:

```php
static::deleting(function (Course $course) {
    $course->loadMissing(['materials', 'assignments.submissions']);

    foreach ($course->materials as $mat) {
        if ($mat->file_path && Storage::disk('local')->exists($mat->file_path)) {
            Storage::disk('local')->delete($mat->file_path);
        }
    }

    foreach ($course->assignments as $asg) {
        foreach ($asg->submissions as $sub) {
            if ($sub->file_path && Storage::disk('local')->exists($sub->file_path)) {
                Storage::disk('local')->delete($sub->file_path);
            }
        }
    }
});
```

**Pesan commit yang digunakan:**

```bash
git commit -m "fix(review): wrap course cascade deletion in DB transaction and handle physical file cleanup"
```

**Hasil Pengujian:**

Pengujian `FileUploadSecurityTest::test_admin_can_delete_course_and_related_files()` berhasil dijalankan. Hasil ini menunjukkan bahwa proses penghapusan mata kuliah beserta berkas-berkas terkait tetap berjalan dengan baik setelah perbaikan diterapkan.

---

## 5. Checkpoint Evaluasi Mandiri Minggu 13

- [x] **Jumlah Commit:** Terdapat 50 commit atas nama Shabrina Putri Az-Zahra (`@ItsShika24` / `@shabrinakeif`) yang tercatat dalam riwayat proyek.

- [x] **Pemahaman tentang Kebocoran Secret:** Memahami bahwa perintah `git rm --cached .env` hanya menghapus file `.env` dari commit terbaru, tetapi data rahasia yang pernah tersimpan masih bisa ditemukan di riwayat Git. Oleh karena itu, perlu dilakukan penggantian kredensial (*credential rotation*) dan pembersihan riwayat Git menggunakan `git-filter-repo`.

- [x] **Pentingnya Micro-PR:** Memahami bahwa PR dengan perubahan yang lebih kecil (kurang dari 300 baris perubahan) lebih mudah diperiksa secara menyeluruh, mengurangi risiko konflik saat penggabungan kode (*merge conflict*), serta memudahkan pencarian kesalahan (*regression bug*) dan pengembalian kode ke versi sebelumnya (*rollback*).

- [x] **Keterbatasan Evaluasi Commit:** Memahami bahwa jumlah commit dari `git shortlog` belum tentu menunjukkan kontribusi seseorang secara keseluruhan karena informasi nama pembuat commit dapat diubah. Oleh karena itu, kontribusi juga perlu dinilai dari pemahaman terhadap kode, arsitektur sistem, dan kemampuan menjelaskan hasil pekerjaan.

- [x] **Pelaksanaan Peer Review Mandiri (10%):** Berhasil melakukan review terhadap PR #34 milik Tresia (`@Tresiauyang`) dari repositori Kelompok 07. Review mencakup tiga kategori, yaitu kebersihan kode, performa, serta keamanan dan pengujian.

- [x] **Penyelesaian Review yang Diterima (Poin 7 & 8):** Berhasil menanggapi masukan dari Kelompok 05 pada PR #50 dengan baik. Masukan tersebut juga sudah ditindaklanjuti melalui perbaikan kode pada `CourseController.php` dan dikirimkan menggunakan commit `fix(review): ...`.
