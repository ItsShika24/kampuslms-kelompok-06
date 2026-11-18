# Laporan Praktikum Minggu 13 — Kolaborasi, Version Control, dan Peer Review
## Nama: Oktavia Nur Rahmadani
## NIM: 10241060
## Kelompok: 06 (EduKampus)
**Peran Utama:** Frontend & Form Validation Lead (Frontend & QA Lead)  
**Repositori:** KampusLMS (Laravel 12)  
**Dokumen Pendukung:** [`.github/pull_request_template.md`](../../.github/pull_request_template.md) & [`README.md`](../../README.md)  

---

## 1. Tahap 1: READ — Analisis Riwayat Git Pribadi & Kelompok (30 Menit)

Sesuai instruksi Modul 13 Subbab 13.3, dilakukan inspeksi mendalam terhadap riwayat commit pada repositori `kampuslms-kelompok-06` menggunakan perintah Git resmi:
```bash
git shortlog -sn --all
git log --author="Victoire" --oneline
git log --author="Oktavia" --oneline
git log --author="Victoire\|Oktavia" --stat
```

Berikut adalah hasil refleksi mandiri atas 5 pertanyaan evaluasi riwayat version control:

---

### 1.1 Jumlah Commit Pribadi vs Anggota Tim
Berdasarkan log repositori resmi per Minggu 13 (`git shortlog -sn --all`):
* **Tika Mila Wahyuni:** 103 commit (~57% total repositori)
* **Shabrina Putri Azzahra:** 44 commit (gabungan akun `@ItsShika24` [28 commit] dan `@shabrinakeif` [16 commit])
* **Raihandy Wijaya:** 18 commit (tercatat langsung dengan nama `Raihandy Wijaya`, di luar merge commit akun bersama)
* **Oktavia Nur Rahmadani:** 9 commit (gabungan akun `@06Victoire` [8 commit] dan `Oktavia Nur Rahmadani` [1 commit], keduanya terikat pada email kampus resmi `10241060@student.itk.ac.id`)
* **Total Commit Repositori:** ~180+ commit

**Analisis Reflektif:**
Porsi commit saya tercatat sebanyak 9 commit (~5% dari total repositori). Perbedaan jumlah commit ini disebabkan oleh pembagian karakteristik tanggung jawab teknis dalam tim:
1. Rekan Tika bertindak sebagai Tech Lead yang menangani arsitektur backend, 5 Laravel Policies, perbaikan kueri global, serta penggabungan (*merging*) dari seluruh branch anggota, sehingga riwayat commit-nya paling tinggi.
2. Sebagai **Frontend & Form Validation Lead**, alur kerja saya berfokus pada pembangunan antarmuka Blade, Form Requests, pembersihan tampilan, dan pengujian responsivitas (*QA*). Pengerjaan modul frontend diselesaikan dalam bentuk blok fitur utuh (*feature batching*) yang kemudian digabungkan melalui Pull Request.
3. Sebagai contoh, commit `9642636` (*halaman rekap nilai dan dropdown notifikasi*) mencakup 550 baris kode sekaligus, commit `e6feaa4` (*pembaruan tampilan tugas dan submission*) mencakup 599 baris delta, dan commit `c8f4e99` (*laporan deployment, troubleshooting Nginx, dan checklist mobile testing*) mencakup 767 baris perubahan.
4. Ini membuktikan temuan penting Modul 13 bahwa **jumlah commit (`git shortlog`) bukanlah tolok ukur tunggal kontribusi**. Kontribusi frontend yang berdampak luas sering terkonsolidasi dalam sedikit commit berbobot besar (*high-impact commits*).

---

### 1.2 Konsistensi Commit Mingguan
Linimasa commit saya dari awal semester hingga Minggu 13 terdistribusi sebagai berikut:
* **Minggu 1 (1 Sep 2026):** Commit `de0f8f0` — Setup awal, catatan modul 1, dan artefak tangkapan layar.
* **Minggu 2 (6 Sep 2026):** Commit `f2e1ad4` — Dokumentasi praktikum modul 2 (Blade & Controller).
* **Minggu 3 & 4 (Pertengahan Sep – Awal Okt):** Tidak terdapat commit mandiri di branch utama pada rentang tanggal tersebut karena pengerjaan form request dan styling antarmuka dikerjakan pada branch eksperimen lokal dan dikompilasi ke laporan gabungan w03–w07 pada 11 Oktober (`1e82f9e`).
* **Minggu 5 (30 Sep 2026):** Commit `edb59cb` — Perbaikan keamanan, routing, dan navigasi modul 5.
* **Minggu 6 & 7 (11 Okt 2026):** Commit `1e82f9e` — Konsolidasi dokumentasi praktikum w03, w04, w05, w06, dan w07.
* **Minggu 8 (Pertengahan Okt):** Periode Ujian Tengah Semester (UTS) — tidak ada aktivitas koding baru.
* **Minggu 9 (20 Okt 2026):** Commit `e6feaa4` — Pembaruan antarmuka tugas (`assignments/show.blade.php`) dan form pengumpulan (`submissions/show.blade.php`).
* **Minggu 10 (28 Okt 2026):** Commit `9642636` — Implementasi halaman rekap nilai (`grades/index.blade.php`) dan komponen dropdown lonceng notifikasi pada master layout.
* **Minggu 11 (3 Nov 2026):** Commit `7b1fd25` & `dc63bb9` — Penyusunan laporan performa frontend dan tabel audit komparasi di `docs/performa.md`.
* **Minggu 12 (10 Nov 2026):** Commit `c8f4e99` — Dokumentasi deployment produksi, checklist pengujian tampilan perangkat bergerak (*mobile testing*), dan troubleshooting web server.
* **Minggu 13 (17 Nov 2026):** Pengerjaan modul 13 (simulasi uji kebocoran kredensial, verifikasi branch protection, refleksi Git mandiri, dan peer review frontend).

**Evaluasi Kendala:**
Terdapat jeda commit pada Minggu 3, 4, dan 8. Kendala utama pada paruh pertama semester adalah kebiasaan menahan perubahan di lokal dalam jangka waktu lama sebelum melakukan commit (*batch committing*). Melalui pembelajaran Modul 13, saya menyadari pentingnya prinsip *continuous integration* dan *frequent atomic commits* agar kemajuan pekerjaan selalu terpantau transparan oleh seluruh anggota tim dan tidak menimbulkan penumpukan konflik saat integrasi.

---

### 1.3 Kepatuhan terhadap *Conventional Commits*
Evaluasi kepatuhan terhadap standar *Conventional Commits* (`feat:`, `fix:`, `docs:`, `style:`, `refactor:`, `chore:`) dari 9 commit riwayat saya:

* **Commit Non-Konvensional (4 Commit — Paruh Awal):**
  1. `de0f8f0` — *"commit pertama Via"*
  2. `f2e1ad4` — *"minggu-02-oktavia.md"*
  3. `1e82f9e` — *"Updating Catatan Oktavia w03, w04, w05, w06, w07"*
  4. `e6feaa4` — *"Update tampilan tugas dan pengumpulan"*
* **Commit Konvensional (5 Commit — Paruh Kedua):**
  1. `edb59cb` — `fix: perbaiki keamanan dan routing week 5`
  2. `9642636` — `feat(ui): implementasi halaman rekap nilai dan dropdown lonceng notifikasi (Oktavia Nur Rahmadani)`
  3. `7b1fd25` — `docs: tambah laporan performa minggu 11`
  4. `dc63bb9` — `docs(m11): lengkapi laporan minggu 11 oktavia, tabel komparasi di docs/performa.md, dan hapus berkas duplikat`
  5. `c8f4e99` — `feat(docs): tambah laporan minggu 12 oktavia, log troubleshooting, dan checklist mobile testing di deployment.md`

**Persentase Kepatuhan:** Sekitar **55,6%** (5 dari 9 commit).  
Terlihat transisi kedewasaan teknis yang sangat jelas: di awal semester, pesan commit masih bercampur dengan gaya informal dan deskripsi berkas semata. Memasuki Minggu 10 hingga 13, seluruh commit telah mematuhi 100% kaidah *Conventional Commits* dengan penulisan tipe (`feat`, `docs`, `fix`) dan cakupan modul yang jelas.

---

### 1.4 Temuan Pesan Commit Buruk & Penulisan Ulang yang Benar
Dari riwayat Git terdahulu, ditemukan 2 commit dengan pesan yang sangat lemah:

1. **Commit `de0f8f0` — *"commit pertama Via"***
   * **Kelemahan Fatal:** Pesan ini sangat subjektif, bernuansa obrolan informal, tidak menyebutkan modul/komponen apa yang dikerjakan, dan tidak memberi konteks bagi pengembang lain yang membaca riwayat proyek.
   * **Penulisan Ulang Standar Industri:**
     ```text
     docs(m01): susun catatan pengenalan arsitektur laravel mvc dan dokumentasi praktikum minggu 1
     ```

2. **Commit `f2e1ad4` — *"minggu-02-oktavia.md"***
   * **Kelemahan Fatal:** Pesan hanya menduplikat nama berkas yang dibuat tanpa adanya kata kerja tindakan, tipe perubahan, maupun deskripsi substansi materi.
   * **Penulisan Ulang Standar Industri:**
     ```text
     docs(m02): tambahkan laporan praktikum routing controller blade dan artefak tangkapan layar
     ```

---

### 1.5 Temuan Commit Terbesar (`git log --stat`) & Analisis Pemecahan
Berdasarkan keluaran `git log --stat`:
* **Commit Terbesar yang Teridentifikasi:**  
  `Commit c8f4e99 — feat(docs): tambah laporan minggu 12 oktavia, log troubleshooting, dan checklist mobile testing di deployment.md`
* **Statistik Diff:** **3 files changed, 761 insertions(+), 6 deletions(-)** (Total 767 baris delta).
* **Commit Kode UI Terbesar:**  
  `Commit 9642636 — feat(ui): implementasi halaman rekap nilai dan dropdown lonceng notifikasi (Oktavia Nur Rahmadani)`  
  **2 files changed, 529 insertions(+), 21 deletions(-)** (Total 550 baris delta pada `resources/views/grades/index.blade.php` dan `resources/views/components/layout.blade.php`).

**Analisis Kritis (*Giant Commit*):**
Commit `c8f4e99` menggabungkan tiga domain yang berbeda sekaligus:
1. Panduan build modul 10 (`panduan-build-oktavia.md` — 624 baris).
2. Laporan individu evaluasi deployment minggu 12 (`laporan-minggu-12-oktavia.md` — 106 baris).
3. Modifikasi panduan operasional server bersama (`docs/deployment.md` — 37 baris) mencakup log perbaikan Nginx dan tabel verifikasi antarmuka mobile.

**Rekomendasi Pemecahan Menjadi *Atomic Commits*:**
Commit besar tersebut semestinya dipecah menjadi 3 commit terpisah yang lebih fokus dan mandiri:
1. `docs(m10): arsipkan panduan teknis pengerjaan build modul 10`
2. `feat(deployment): tambah log troubleshooting nginx dan instrumen checklist audit mobile qa pada deployment.md`
3. `docs(m12): terbitkan laporan mingguan evaluasi deployment produksi oktavia`

---

## 2. Tahap 2: BREAK — Simulasi Eksperimen Proteksi Git & CI (50 Menit)

### 2.1 Simulasi Kebocoran Kredensial `.env` & Eksperimen `git rm --cached` (BREAK #1 & #2)

Dalam latihan pengujian keamanan repositori sesuai Modul 13.3, disimulasikan skenario kecelakaan umum pengembang pemula: **secara tidak sengaja memasukkan file konfigurasi rahasia (`.env`) ke dalam commit Git**.

#### Langkah Percobaan yang Dijalankan:
1. **Penyelamatan Kredensial Nyata:**  
   File `.env` produksi lokal diamankan terlebih dahulu ke direktori luar agar tidak tercemar:
   ```bash
   cp .env ../.env.asli.jangan-disentuh
   ```
2. **Pembuatan File Secret Palsu untuk Latihan:**
   ```bash
   echo "APP_KEY=base64:PALSU_UNTUK_LATIHAN" > .env
   echo "DB_PASSWORD=palsu_password_latihan" >> .env
   ```
3. **Pembuatan Branch Eksperimen & Commit File `.env`:**
   ```bash
   git checkout -b test/secret-leak-simulation
   git add -f .env
   git commit -m "test(secret): simulasi ketidaksengajaan commit file .env"
   ```
4. **Upaya Penanganan Naif Menggunakan `git rm --cached`:**
   Pengembang yang belum memahami arsitektur Git biasanya mencoba menghapus file dari tracking Git dengan perintah:
   ```bash
   git rm --cached .env
   git commit -m "fix(secret): coba hapus file .env dengan git rm cached"
   ```
5. **Pemeriksaan Bukti Riwayat Git (`git log -p -n 2`):**
   Hasil inspeksi log commit menunjukkan fenomena kritis berikut:
   ```text
   commit 0d1949ba7b6fc0d5d4abea87b326beb195b25cb4
   Author: 06Victoire <10241060@student.itk.ac.id>
   Date:   Tue Nov 17 14:42:42 2026 +0800

       fix(secret): coba hapus file .env dengan git rm cached

   diff --git a/.env b/.env
   deleted file mode 100644
   index cefcfa2..0000000
   --- a/.env
   +++ /dev/null
   @@ -1,2 +0,0 @@
   -APP_KEY=base64:PALSU_UNTUK_LATIHAN
   -DB_PASSWORD=palsu_password_latihan

   commit 1790a882df7cf6c6bebcb43ce0ca314bd94155d3
   Author: 06Victoire <10241060@student.itk.ac.id>
   Date:   Tue Nov 17 14:42:29 2026 +0800

       test(secret): simulasi ketidaksengajaan commit file .env

   diff --git a/.env b/.env
   new file mode 100644
   index 0000000..cefcfa2
   --- /dev/null
   +++ b/.env
   @@ -0,0 +1,2 @@
   +APP_KEY=base64:PALSU_UNTUK_LATIHAN
   +DB_PASSWORD=palsu_password_latihan
   ```

#### Pelajaran Penting & Kesimpulan Keamanan:
* **`git rm --cached` Bukan Solusi Kebocoran Data:** Perintah `git rm --cached` hanya menghapus file dari staging tree (*index*) untuk commit terbaru di masa depan. Berkas `.env` memang tampak hilang di commit paling atas (*HEAD*), tetapi **seluruh teks kredensial rahasia (`APP_KEY` dan `DB_PASSWORD`) tetap terekam utuh dan abadi di dalam riwayat commit sebelumnya (`1790a88`)**.
* **Dampak Jika Ter-Push ke Publik:** Siapa pun yang mengkloning repositori dapat mengeksekusi `git checkout 1790a88` atau menjalankan `git log -p` untuk membaca kunci enkripsi dan basis data tersebut.
* **Protokol Mitigasi yang Benar:**
  1. Jika kredensial produksi sungguhan sempat ter-push ke GitHub, rahasia tersebut **wajib dianggap telah bocor seketika (*compromised*)**.
  2. Tindakan pertama bukan sekadar menghapus commit, melainkan **merotasi total seluruh kredensial** (generate ulang `APP_KEY`, ubah kata sandi basis data di server produksi, dan ganti token API pihak ketiga).
  3. Riwayat Git wajib dibersihkan secara permanen menggunakan utilitas seperti `git-filter-repo` atau *BFG Repo-Cleaner*, lalu di-force push dengan koordinasi seluruh anggota tim.

*(Setelah eksperimen selesai, file `.env` asli segera dipulihkan kembali dan branch eksperimen lokal dihapus).*

---

### 2.2 Verifikasi Proteksi Branch & Penegakan Status CI (BREAK #4 & #5)

Dalam tim Kelompok 06, proteksi branch pada `main` telah dikonfigurasi oleh Tech Lead (Tika). Dilakukan pengujian terhadap dua mekanisme pengamanan utama:

#### 1. Verifikasi Larangan *Direct Push* ke Branch `main` (BREAK #4):
* **Skenario:** Pengembang mencoba melakukan commit dan push langsung dari terminal lokal ke branch utama tanpa melalui alur Pull Request:
  ```bash
  git checkout main
  git push origin main
  ```
* **Hasil Observasi:**  
  GitHub menolak pengiriman langsung tersebut dengan pesan galat proteksi:
  ```text
  remote: error: GH006: Protected branch update failed for refs/heads/main.
  remote: error: Changes must be made through a pull request.
  To https://github.com/ItsShika24/kampuslms-kelompok-06.git
   ! [remote rejected] main -> main (protected branch hook declined)
  ```
* **Makna Praktis:**  
  Pengaturan ini mencegah terjadinya kecelakaan fatal di mana seorang pengembang secara tidak sengaja menimpa branch produksi dengan kode lokal yang belum teruji atau belum direview.

#### 2. Verifikasi Penegakan *Status Checks* CI (BREAK #5):
* **Skenario:** Ketika sebuah Pull Request dibuka dengan kode yang sengaja merusak pengujian (*broken test* atau kesalahan sintaks), pipeline otomatis GitHub Actions (`.github/workflows/ci.yml`) mendeteksi kegagalan tersebut (*status check FAILED / Red Cross ❌*).
* **Hasil Observasi di Antarmuka GitHub:**
  * Tombol **"Merge pull request"** berada dalam status terkunci (*disabled/greyed out*).
  * Muncul peringatan resmi: *"Required status checks must pass before merging"*.
* **Makna Praktis:**  
  Branch `main` selalu dijamin berada dalam status sehat (*green build*). Tidak ada kode yang dapat masuk ke lingkungan produksi jika tidak berhasil melewati unit testing dan feature testing otomatis.

---

## 3. Tahap 3: Eksekusi Peer Review Repositori Kelompok Lain (BUILD — Bobot Nilai 10%)

Sebagai pemegang peran **Frontend & Form Validation Lead**, saya melakukan peninjauan kode (*peer review*) secara independen terhadap repositori rekan kelompok sasaran (**Kelompok 07**) pada Pull Request yang dikerjakan oleh **Zalfa Putri Sopyandi** (`ZalfaPutriSopyandi`):

* **Tautan PR Sasaran:** [PR #31: `feat:update fitur upload materials`](https://github.com/Vexusian/kampuslms-B-kelompok-07/pull/31#issuecomment-6975582632)
* **Akun Reviewer:** `@06Victoire` (Oktavia Nur Rahmadani)
* **Fokus Peninjauan:** **Keamanan (Validasi & Integritas Data)**, **Performa (N+1 Query & Cascade Loop)**, dan **Struktur & Keterbacaan Kode (Controller Ramping & Logika View)**.

Berikut adalah 3 temuan hasil peninjauan kode resmi yang telah saya kirimkan di kolom komentar PR tersebut:

---

### 🔍 Temuan 1: Keamanan (Validasi & Integritas Data — Input File Nullable Tanpa Aturan Kondisional)
* **Lokasi Berkas & Baris:** `app/Http/Controllers/MaterialController.php:53-63` dan `MaterialController.php:84-99`
* **Deskripsi Masalah:**  
  Pada method `store()`, aturan validasi untuk input `file` disetel `nullable` tanpa aturan kondisional berbasis tipe:
  ```php
  'type' => ['nullable', 'in:file,link'],
  'file' => ['nullable', 'file', 'mimes:pdf,pptx,docx', 'max:20480'],
  'external_url' => ['nullable', 'url', 'max:2048'],
  ```
  Jika pengguna memilih opsi `type = 'file'` tetapi tidak memilih berkas apa pun (input file kosong), validasi `$request->validate()` tetap berhasil lolos (*pass*). Kode kemudian masuk ke blok `else` dan membuat record baru dengan `file_path = null` serta `original_name = null`. Record materi berjenis fisik ini akan tersimpan tanpa berkas sama sekali.
* **Risiko Teknis:**  
  Integritas basis data terganggu (*corrupted state*). Ketika mahasiswa membuka materi atau menekan tombol unduh, sistem akan mengalami *unhandled error* atau *abort 404* karena `$material->file_path` kosong.
* **Saran Perbaikan Konkret:**  
  Gunakan validasi kondisional bawaan Laravel `required_if` atau `Rule::requiredIf` agar berkas wajib diunggah hanya saat tipenya adalah `file`, dan URL wajib diisi jika tipenya adalah `link`:
  ```php
  'file' => [
      'required_if:type,file',
      'nullable',
      'file',
      'mimes:pdf,pptx,docx',
      'max:20480',
  ],
  'external_url' => [
      'required_if:type,link',
      'nullable',
      \Illuminate\Validation\Rules\File::class ? null : 'url',
      'max:2048',
  ],
  ```

---

### 🔍 Temuan 2: Performa (N+1 Query pada Daftar Materi & Query Loop Cascade)
* **Lokasi Berkas & Baris:** `app/Http/Controllers/MaterialController.php:23` dan `resources/views/materials/index.blade.php:89-106`
* **Deskripsi Masalah:**  
  Pada `MaterialController::index()`, pemanggilan data materi dilakukan melalui:
  ```php
  $materials = $course->materials()->latest()->paginate(15);
  ```
  Kueri ini tidak melakukan *eager loading* untuk relasi `course`. Sementara itu, di dalam file tampilan `resources/views/materials/index.blade.php`, setiap baris melakukan pengecekan otorisasi menggunakan direktif Blade:
  ```blade
  @can('update', $material)
  @can('delete', $material)
  ```
  Pada `MaterialPolicy`, method `update` dan `delete` memeriksa kepemilikan dosen dengan mengevaluasi `$material->course->lecturer_id === $user->id`. Karena relasi `course` belum di-load, Eloquent akan mengeksekusi kueri terpisah (`SELECT * FROM courses WHERE id = ?`) untuk setiap item materi pada halaman tersebut (**N+1 Query Problem**).
* **Risiko Teknis:**  
  Terjadi lonjakan kueri SQL (15 kueri tambahan per halaman). Semakin banyak materi ditampilkan, latensi respon basis data akan meningkat secara linier.
* **Saran Perbaikan Konkret:**  
  Tambahkan eager loading relasi `course` pada kueri pagination di controller:
  ```php
  $materials = $course->materials()->with('course')->latest()->paginate(15);
  ```
  *(Catatan tambahan: Pada `app/Models/Course.php:59-63`, pemanggilan `$course->materials()->each(fn($m) => $m->delete())` juga memicu N kueri delete individual saat Course dihapus, padahal foreign key sudah menggunakan `cascadeOnDelete()` dan direktori berkas sudah dibersihkan sekaligus dengan `deleteDirectory()`)*.

---

### 🔍 Temuan 3: Struktur & Keterbacaan Kode (Controller Gemuk & Duplikasi Logika View)
* **Lokasi Berkas & Baris:** `app/Http/Controllers/MaterialController.php:48-70` berbanding `MaterialController.php:145-168` serta `resources/views/courses/show.blade.php:243`
* **Deskripsi Masalah:**  
  1. **Duplikasi Validasi di Controller**: Blok validasi array yang panjang (sekitar 25 baris kode) diduplikasi persis di method `store()` dan `update()`. Meskipun kelas Form Request (`StoreMaterialRequest` dan `UpdateMaterialRequest`) di-type-hint pada parameter method, validasi manual `$request->validate()` tetap dijalankan di dalam controller.
  2. **Logika Presentasi di View**: Di dalam file `courses/show.blade.php` dan `materials/index.blade.php`, terdapat pemanggilan fungsi manipulasi berkas bawaan PHP langsung di lapisan presentasi Blade:
     ```blade
     {{ strtoupper(pathinfo($material->original_name ?? $material->file_path, PATHINFO_EXTENSION)) }}
     ```
* **Risiko Teknis:**  
  Menyalahi prinsip *Single Responsibility* dan DRY (*Don't Repeat Yourself*). Jika terjadi perubahan format berkas atau pesan validasi di masa mendatang, developer harus memperbarui berkas di banyak tempat, meningkatkan risiko inkonsistensi (*technical debt*).
* **Saran Perbaikan Konkret:**  
  - Pindahkan seluruh aturan validasi dan pesan kustom ke dalam kelas `StoreMaterialRequest` dan `UpdateMaterialRequest` agar controller tetap ramping (*skinny controller*).
  - Pindahkan logika penentuan ekstensi berkas ke dalam *Accessor* di Model `Material` (misalnya method `getExtensionAttribute()`), sehingga di dalam view Blade cukup memanggil `$material->extension`:
    ```php
    // app/Models/Material.php
    public function getExtensionAttribute(): string
    {
        return strtoupper(pathinfo($this->original_name ?? $this->file_path ?? '', PATHINFO_EXTENSION));
    }
    ```
