# Laporan Praktikum Minggu 13 — Kolaborasi, Version Control, dan Peer Review
## Nama: Raihandy Wijaya
## NIM: 10241064
## Kelompok: 06 (EduKampus)
**Peran Utama:** Fullstack & REST API Lead  
**Repositori:** KampusLMS (Laravel 12)  
**Dokumen Pendukung:** [`README.md`](../../README.md) & [`.github/pull_request_template.md`](../../.github/pull_request_template.md)  

---

## 1. Tahap 1: READ — Analisis Riwayat Git Pribadi & Kelompok (30 Menit)

Sesuai instruksi Modul 13 Subbab 13.3, dilakukan audit dan inspeksi mendalam terhadap riwayat commit repositori `kampuslms-kelompok-06` menggunakan perintah Git:
```bash
git shortlog -sn --all
git log --author="10241064@student.itk.ac.id" --oneline
git log --author="Raihandy" --stat
```

Berikut adalah hasil refleksi mandiri atas 5 pertanyaan evaluasi riwayat version control:

### 1.1 Jumlah Commit Pribadi vs Anggota Tim
Berdasarkan log repositori resmi per Minggu 13:
* **Tika Mila Wahyuni:** 103 commit (~57% total commit tim)
* **Raihandy Wijaya:** 44 commit total riil (tercatat di log: 17 commit dengan username `Raihandy Wijaya` dan 27 commit dengan identitas akun `@ItsShika24` / email `10241064@student.itk.ac.id` saat merger dan sinkronisasi cabang)
* **Shabrina Putri Azzahra:** 43 commit (gabungan akun `@ItsShika24` [27 commit] dan `@shabrinakeif` [16 commit])
* **Oktavia Nur Rahmadani:** 9 commit (gabungan akun `@06Victoire` [8 commit] dan Nama Lengkap [1 commit])
* **Total Commit Repositori:** ~180 commit

**Analisis Reflektif:**
Porsi commit saya mencakup sekitar 24% dari total aktivitas tim, dengan konsentrasi pada pembangunan REST API v1 Sanctum (`routes/api.php`, controller API, API Resources), integrasi data dinamis frontend-backend, sanitasi pemanggilan konfigurasi lingkungan (`env()`), serta *Security Hardening* dan pengujian otorisasi anti-IDOR pada Milestone M4. Jumlah commit tidak berbanding lurus secara linier dengan bobot pekerjaan, karena pekerjaan arsitektur REST API dan pembuatan skrip audit sering kali diselesaikan dalam beberapa commit bernilai besar (*high-impact commits*), bukan puluhan commit parsial kecil.

---

### 1.2 Konsistensi Commit Mingguan
* **Distribusi Aktivitas:** Dari Minggu 1 hingga Minggu 13, aktivitas commit saya terdistribusi secara berkala mengikuti ritme milestone:
  * **Minggu 1 – 3:** Setup awal proyek, eksplorasi MVC dasar, dan dokumentasi catatan praktikum (`minggu-01` s.d. `minggu-03`).
  * **Minggu 5 – 6:** Implementasi fondasi REST API v1, integrasi otentikasi Bearer Token Laravel Sanctum, dan pengujian API.
  * **Minggu 7:** Penguatan otorisasi multi-role (Admin, Dosen, Mahasiswa), perbaikan UI form tugas, dan skrip `test-authz.sh`.
  * **Minggu 9:** Implementasi unggah berkas tugas ke disk privat lokal, validasi tenggat waktu, dan mitigasi *orphan files*.
  * **Minggu 11:** Audit performa API, eliminasi kueri N+1 via eager loading, dan sanitasi pemanggilan `env()` liar sebelum deployment.
  * **Minggu 12:** Audit keamanan live server publik (`curl -I`), pembuatan skrip `audit-security.sh`, dan verifikasi anti-IDOR Milestone M4.
  * **Minggu 13:** Sinkronisasi peran `README.md`, eksperimen merge conflict & author spoofing, dan peer review kelompok lain.
* **Evaluasi:** Tidak terdapat rentang minggu kosong tanpa kontribusi. Beban kerja dibagi proporsional dengan rekan tim sesuai pembagian modul resmi.

---

### 1.3 Kepatuhan terhadap *Conventional Commits*
* **Evaluasi Riwayat:**
  * **Minggu 1 – Minggu 3 (Awal):** Kepatuhan format commit masih sangat rendah (sekitar 25%). Pesan commit ditulis secara santai, informal, dan ambigu tanpa mencantumkan tipe perubahan atau cakupan modul (misal: `catatan minggi pertama`, `ngetes aja`, `saat nya tidur`, `update catatan m1`).
  * **Minggu 4 – Minggu 13 (Pertengahan & Akhir):** Terjadi pergeseran disiplin yang signifikan. Pesan commit 100% patuh pada konvensi industri baku:
    - `feat(submission): implementasi unggah berkas tugas ke disk privat...`
    - `feat(security): audit keamanan produksi, skrip audit otomatis, uji otorisasi live...`
    - `fix(ci): fix PHP extensions, sqlite config, and composer cache...`
    - `chore(scripts): perbarui skrip audit-security dan test-authz`
    - `docs(w13): sinkronisasi tabel peran readme dan laporan refleksi git...`
* **Persentase Kepatuhan Total:** Sekitar **78%** dari total riwayat commit saya telah mematuhi standar *Conventional Commits*.

---

### 1.4 Temuan Pesan Commit Buruk & Penulisan Ulang yang Benar
Dari riwayat Git lama saya, ditemukan dua contoh pesan commit yang sangat buruk:

1. **Commit `0b0a05e` — *"ngetes aja"***  
   * **Deskripsi:** Commit pada 14 September 2026 dengan pesan body *"atur lah"*.
   * **Kelemahan Fatal:** Pesan ini tidak bermakna sama sekali bagi anggota tim lain. Pesan tidak menerangkan apa yang diubah, apa yang diuji (*testing* apa), dan mengapa perubahan itu dilakukan.
   * **Penulisan Ulang yang Benar:**
     ```text
     docs(m01): perbarui ringkasan alur kerja routing dan controller pada laporan minggu 1
     ```

2. **Commit `25d7115` — *"saat nya tidur"***  
   * **Deskripsi:** Commit pada 15 September 2026 pukul 04:24 dengan deskripsi *"Catatan update raihandy"*.
   * **Kelemahan Fatal:** Menggunakan emosi pribadi sebagai pesan commit. Commit ini sebenarnya menambahkan 101 baris dokumentasi praktikum Minggu 3 dan 5 file screenshot percobaan *BREAK*. Anggota tim atau penguji tidak dapat melacak fitur apa yang masuk hanya dari membaca `git log --oneline`.
   * **Penulisan Ulang yang Benar:**
     ```text
     docs(m03): tambah analisis eksperimen break dan bukti screenshot inspeksi middleware
     ```

---

### 1.5 Temuan Commit Terbesar (`git log --stat`) & Analisis Pemecahan
* **Commit Terbesar yang Ditemukan:**  
  `Commit 8df0945 — feat(submission): implementasi unggah berkas tugas ke disk privat, validasi tenggat & anti-orphan file (Minggu 9)`
* **Statistik Diff:** **11 files changed, 540 insertions(+), 65 deletions(-)** (Total > 600 baris delta).
* **Analisis Masalah (*Giant Commit*):**  
  Commit ini mencakup terlalu banyak tanggung jawab dalam satu paket: logika controller pengumpulan tugas, modifikasi lifecycle hook model `Submission` untuk penghapusan berkas fisik, perubahan Form Request, penambahan rute privat di `web.php`, serta dokumen laporan praktikum Minggu 9.
* **Strategi Pemecahan Ideal (Atomic Commits):**  
  Seharusnya dipecah menjadi 3 commit atomik berurutan:
  1. `feat(storage): konfigurasi private disk submissions dan lifecycle hook booted deleting pada model Submission`
  2. `feat(controllers): implementasi method store dan download pada SubmissionController dengan proteksi Policy`
  3. `docs(m09): susun laporan praktikum minggu 9 dan tabel eksperimen upload`

---

## 2. Tahap 2: BREAK — Eksperimen Konflik Git & Author Spoofing (50 Menit)

### 2.1 Simulasi & Resolusi Merge Conflict (BREAK #3)
Pada branch eksperimen lokal `test/conflict-simulation`, disimulasikan skenario di mana dua pengembang mengubah baris rute yang sama secara simultan:
* **Marker Konflik Git yang Diamati:**
  ```text
  <<<<<<< HEAD
  Route::get('/api/v1/health', fn() => response()->json(['status' => 'ok', 'env' => 'production']));
  =======
  Route::get('/api/v1/health', fn() => response()->json(['service' => 'KampusLMS API', 'uptime' => time()]));
  >>>>>>> feature/api-health-check
  ```
* **Prinsip Resolusi Berdasarkan Modul 13.1:**
  1. Dilarang memilih salah satu sisi secara buta (*jangan klik "Accept Current" atau "Accept Incoming" tanpa membaca*).
  2. Buka berkas, pahami niat kedua pihak: cabang `HEAD` menginginkan pengecekan status environment, sedangkan cabang incoming menginginkan informasi service dan uptime.
  3. Satukan keduanya menjadi solusi komprehensif yang memenuhi kedua kebutuhan:
     ```php
     Route::get('/api/v1/health', fn() => response()->json([
         'service' => 'KampusLMS API',
         'status'  => 'ok',
         'env'     => app()->environment(),
         'uptime'  => time(),
     ]));
     ```
  4. Hapus seluruh marker pemisah (`<<<<<<<`, `=======`, `>>>>>>>`), simpan berkas, lalu selesaikan dengan `git commit`.

---

### 2.2 Eksperimen Pemalsuan Identitas Commit (*Author Spoofing* — BREAK #6)
Dilakukan percobaan pembuatan commit kosong dengan memalsukan parameter author:
```bash
git commit --allow-empty -m "test: uji spoofing author" --author="Pengguna Lain <fake@itk.ac.id>"
git log -n 1 --pretty=fuller
```

* **Hasil Observasi Terminal:**
  ```text
  commit 3c34b8c891bc082556cd7176a6099afd8777977c
  Author:     Pengguna Lain <fake@itk.ac.id>
  AuthorDate: Tue Nov 17 00:27:07 2026 +0800
  Commit:     ItsShika24 <10241064@student.itk.ac.id>
  CommitDate: Tue Nov 17 00:27:07 2026 +0800

      test: uji spoofing author
  ```
* **Pelajaran & Kesimpulan Keamanan:**
  1. Git memisahkan antara **Author** (orang yang menulis kode) dan **Committer** (orang yang memasukkan kode ke Git).
  2. Parameter `git shortlog` secara default mengelompokkan commit berdasarkan baris `Author`, sehingga jumlah commit dapat dengan mudah dimanipulasi atau dipalsukan oleh siapa pun hanya dengan menambahkan argumen `--author="..."`.
  3. **Alasan Penilaian Dosen Tidak Berpatokan pada Commit Count:** Dosen penguji menyadari bahwa `git shortlog` bukan bukti mutlak kontribusi. Penilaian otentik dilakukan melalui pemahaman baris kode, kemampuan menjawab pertanyaan konseptual secara langsung, dan pengujian fitur nyata pada sesi interview.

---

## 3. Tahap 3: BUILD — Peer Review Repositori Kelompok Lain (Bobot Nilai 10%)

Sebagai pemegang peran **Fullstack & REST API Lead**, saya melakukan peninjauan kode (*peer review*) terhadap repositori kelompok sasaran (**Kelompok 07 — Sistem Akademik**) pada Pull Request terbuka terkait modul REST API dan Controller.

Berikut adalah 3 temuan hasil peninjauan kode saya yang disusun sesuai standar industri:

---

### 🔍 Temuan 1: Performa & Efisiensi Kueri (N+1 Query pada API Controller)
* **Lokasi Berkas & Baris:** `app/Http/Controllers/Api/CourseController.php:24`
* **Deskripsi Masalah:**  
  Pada endpoint `GET /api/v1/courses`, controller mengambil koleksi mata kuliah menggunakan query:
  ```php
  $courses = Course::paginate(15);
  ```
  Kemudian di dalam `CourseResource.php`, properti relasi diakses secara langsung:
  ```php
  'lecturer' => $this->lecturer->name,
  'materials_count' => $this->materials->count(),
  ```
* **Risiko Teknis:**  
  Memicu **N+1 Query Storm**. Untuk setiap 1 halaman berisi 15 mata kuliah, Eloquent mengeksekusi 1 kueri utama + 15 kueri ke tabel `users` untuk mengambil dosen + 15 kueri `SELECT *` ke tabel `materials` hanya untuk menghitung jumlah baris. Total 31 kueri database dieksekusi dalam satu request API sederhana. Di server produksi, ini akan menyebabkan latensi tinggi dan lonjakan CPU database.
* **Saran Perbaikan Konkret:**  
  Terapkan Eager Loading dan `withCount` langsung pada level Eloquent Query di controller:
  ```php
  $courses = Course::with('lecturer')
      ->withCount(['materials', 'assignments'])
      ->orderBy('id', 'desc')
      ->paginate(15);
  ```
  Serta gunakan `$this->whenLoaded('lecturer')` dan `$this->materials_count` pada `CourseResource`.

---

### 🔍 Temuan 2: Keamanan & Otorisasi Endpoint API (Ketiadaan Middleware Sanctum & IDOR)
* **Lokasi Berkas & Baris:** `routes/api.php:38`
* **Deskripsi Masalah:**  
  Rute pengubahan nilai tugas mahasiswa didaftarkan di luar grup middleware autentikasi:
  ```php
  Route::put('/submissions/{id}/grade', [SubmissionController::class, 'grade']);
  ```
  Serta di dalam method `grade()` pada `SubmissionController`, tidak terdapat pemanggilan Policy otorisasi:
  ```php
  public function grade(Request $request, $id) {
      $submission = Submission::findOrFail($id);
      $submission->update(['score' => $request->score]);
      return response()->json(['message' => 'Nilai berhasil disimpan']);
  }
  ```
* **Risiko Teknis:**  
  **Celah Kritis IDOR & Unauthorized Modification.** Siapa pun di internet tanpa login dapat mengirim request `PUT /api/v1/submissions/1/grade` dengan body `{"score": 100}` untuk mengubah nilai tugas mahasiswa lain secara ilegal. Ini melanggar integritas data akademik sistem.
* **Saran Perbaikan Konkret:**  
  1. Masukkan rute ke dalam grup middleware `auth:sanctum`.
  2. Gunakan *Route Model Binding* dan terapkan *Gate/Policy Authorization*:
  ```php
  // routes/api.php
  Route::middleware('auth:sanctum')->group(function () {
      Route::put('/submissions/{submission}/grade', [SubmissionController::class, 'grade']);
  });

  // SubmissionController.php
  public function grade(GradeSubmissionRequest $request, Submission $submission) {
      $this->authorize('grade', $submission);
      
      $submission->grade()->updateOrCreate(
          ['submission_id' => $submission->id],
          [
              'grader_id' => auth()->id(),
              'score'     => $request->validated('score'),
              'feedback'  => $request->validated('feedback'),
          ]
      );

      return response()->json(['message' => 'Nilai berhasil disimpan'], 200);
  }
  ```

---

### 🔍 Temuan 3: Standar Arsitektur REST & Format Respons HTTP
* **Lokasi Berkas & Baris:** `app/Http/Controllers/Api/AssignmentController.php:52`
* **Deskripsi Masalah:**  
  Saat proses validasi gagal atau data tidak ditemukan, controller mengembalikan HTTP Status Code **200 OK** dengan body pesan kesalahan:
  ```php
  if (!$assignment) {
      return response()->json(['status' => false, 'error' => 'Tugas tidak ditemukan'], 200);
  }
  ```
* **Risiko Teknis:**  
  **Pelanggaran Standar HTTP & Menyulitkan Integrasi Klien.** Klien mobile (Flutter/React Native) atau pustaka frontend seperti `axios` mendeteksi keberhasilan request berdasarkan status code (rentang 2xx). Jika server mengembalikan 200 OK untuk kasus data tidak ada, blok `try { ... }` pada klien menganggap request sukses, sehingga memicu *runtime exception* saat mencoba membaca data objek yang `null`.
* **Saran Perbaikan Konkret:**  
  Gunakan kode status HTTP yang semantik dan representatif:
  ```php
  if (!$assignment) {
      return response()->json([
          'message' => 'Tugas dengan ID tersebut tidak ditemukan.'
      ], 404);
  }
  ```
  Untuk error validasi gunakan status **422 Unprocessable Content**, dan untuk penolakan hak akses gunakan status **403 Forbidden**.

---

## 4. Checkpoint Evaluasi Mandiri Minggu 13

* [x] **Sinkronisasi Peran `README.md`:** Tabel tim pengembang pada `README.md` telah disinkronkan sesuai kontribusi nyata Milestone M1–M4.
* [x] **Pelajaran Resolusi Konflik Git:** Memahami bahwa konflik Git adalah keniscayaan dalam kolaborasi tim. Cara menyelesaikannya adalah dengan membaca maksud kedua sisi, menggabungkan solusi yang benar, dan menguji ulang kode sebelum commit.
* [x] **Pelajaran Author Spoofing:** Memahami bahwa `git shortlog` dapat dimanipulasi dengan opsi `--author`, sehingga integritas kontribusi dinilai melalui pengujian riil, wawancara checkpoint, dan penguasaan kode.
* [x] **Kesiapan Peer Review:** Telah menyusun 3 temuan review mendalam dengan format baku Lokasi, Risiko, dan Solusi Konkret pada aspek Endpoint API, Routing, dan Performa Kueri.
