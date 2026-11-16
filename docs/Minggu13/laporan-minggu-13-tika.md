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

## 3. Tahap 3: Kesiapan Peer Review (Komponen Nilai 10% — Individu)

Sesuai rubrik penilaian Subbab 13.4, saya telah menyiapkan instrumen peninjauan kode (*code review checklist*) untuk mengevaluasi Pull Request kelompok lain (berdasarkan alur rotasi melingkar di kelas yang ditetapkan dosen).

Peninjauan akan dilakukan secara mandiri menggunakan akun GitHub pribadi (`@tikaacode`) dengan fokus utama pada **Keamanan (Security)**, didukung aspek performa dan struktur kode:

### 3.1 Rubrik 3 Kategori Temuan Wajib
Setiap temuan akan memuat unsur: **Lokasi (`berkas:baris`), Risiko, dan Saran konkret**.

1. **Kategori 1: Keamanan (Security)**
   * **Area Periksa:** Keberadaan `Gate::authorize()` atau `$this->authorize()` pada seluruh method Controller yang memanipulasi data privat (unduhan file, pengubahan nilai tugas, mutasi course).
   * **Target Deteksi:** Kerentanan IDOR (*Insecure Direct Object Reference*), kebocoran token otentikasi, atau form input tanpa sanitasi server-side.
2. **Kategori 2: Performa & Efisiensi Kueri (Performance)**
   * **Area Periksa:** Masalah N+1 Query pada relasi Eloquent di controller atau looping Blade view.
   * **Target Deteksi:** Kueri relasi di dalam perulangan `foreach` tanpa eager loading `with()`, ketiadaan pagination pada dataset berukuran besar (`->get()` tanpa batas).
3. **Kategori 3: Struktur & Keterbacaan Kode (Code Quality)**
   * **Area Periksa:** Pelanggaran prinsip *Separation of Concerns*.
   * **Target Deteksi:** Logika bisnis atau kueri database mentah yang ditulis di dalam file Blade view, *Fat Controller* (> 50 baris per method), atau sisa debugging (`dd()`, `dump()`, `console.log`).

---

## 4. Checkpoint Evaluasi Mandiri Minggu 13

* [x] **Jumlah Commit:** 101 commit atas nama Tika Mila Wahyuni (tercatat di `git shortlog -sn`).
* [x] **Pelajaran Kebocoran Secret:** Paham penuh bahwa `git rm --cached .env` hanya menghapus file dari commit terbaru (*HEAD*), tetapi riwayat nilai rahasia tetap tersimpan di riwayat commit lama Git. Mitigasi wajibnya adalah rotasi kredensial produksi dan pembersihan riwayat via `git-filter-repo`.
* [x] **Pentingnya PR Kecil:** PR kecil (1 fitur = 1 PR) membuat review kode efektif, mengurangi risiko merge conflict, dan mempermudah pelacakan regresi bug.
* [x] **Keterbatasan Evaluasi Commit:** Memahami bahwa `git shortlog` dapat dimanipulasi dengan opsi `--author`, sehingga integritas kontribusi dinilai melalui pengujian riil, wawancara checkpoint, dan kemampuan menjelaskan logika kode.
